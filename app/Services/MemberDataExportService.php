<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PrivacyRequest;
use App\Models\PrivacyRequestDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberDataExportService
{
    /**
     * Compile complete Subject Access Request export payload for a member.
     */
    public static function compileMemberData(Member $member): array
    {
        // 1. Core Profile
        $profile = [
            'membership_number' => $member->membership_id_assigned ?: 'PENDING',
            'title'             => $member->title,
            'full_name'         => $member->full_name,
            'email'             => $member->email,
            'mobile_number'     => $member->mobile_number,
            'dob'               => $member->dob,
            'marital_status'    => $member->marital_status,
            'spouse_name'       => $member->spouse_name,
            'spouse_mobile'     => $member->spouse_mobile,
            'spouse_dob'        => $member->spouse_dob,
            'emergency_contact' => [
                'name'   => $member->emergency_name,
                'mobile' => $member->emergency_mobile,
            ],
            'address'           => [
                'house_details' => $member->house_details,
                'post_code'     => $member->post_code,
                'residing_area' => $member->residing_area,
            ],
            'membership_type'   => $member->membership_type,
            'status'            => $member->getMembershipStatusLabel(),
            'expiry_date'       => $member->expiry_date,
            'joined_at'         => $member->created_at ? $member->created_at->toIso8601String() : null,
        ];

        // 2. Family Dependents
        $children = $member->children->map(function ($c) {
            return [
                'name'   => $c->child_name,
                'gender' => $c->child_gender ?: $c->sex,
                'dob'    => $c->dob ?: $c->child_dob,
                'age'    => $c->age,
            ];
        })->toArray();

        // 3. Payment Records
        $payments = $member->payments->map(function ($p) {
            return [
                'payment_id'      => $p->id,
                'payment_type'    => $p->payment_type,
                'amount_gbp'      => (float) $p->amount,
                'transaction_ref' => $p->transaction_ref,
                'date'            => $p->created_at ? $p->created_at->toIso8601String() : null,
            ];
        })->toArray();

        // 4. Event Bookings
        $bookings = \App\Models\EventBooking::where('email', $member->email)
            ->orWhere('membership_no', $member->membership_id_assigned)
            ->with('event')
            ->get()
            ->map(function ($b) {
                return [
                    'reference'      => $b->reference_no,
                    'event_title'    => $b->event->title ?? 'Club Event',
                    'headcount'      => [
                        'adults'   => $b->adult_count,
                        'children' => $b->child_count,
                        'students' => $b->student_count,
                        'infants'  => $b->infant_count,
                    ],
                    'total_amount'   => (float) $b->total_amount,
                    'booking_status' => $b->booking_status,
                    'attended'       => $b->check_in_at !== null,
                    'check_in_time'  => $b->check_in_at ? $b->check_in_at->toIso8601String() : null,
                ];
            })->toArray();

        // 5. Consent & Privacy Notice History
        $consents = $member->consents->map(function ($c) {
            return [
                'purpose'      => $c->consent_key,
                'lawful_basis' => $c->lawful_basis,
                'granted'      => $c->granted,
                'granted_at'   => $c->granted_at ? $c->granted_at->toIso8601String() : null,
                'revoked_at'   => $c->revoked_at ? $c->revoked_at->toIso8601String() : null,
            ];
        })->toArray();

        return [
            'subject_access_disclosure' => [
                'data_controller'   => 'Plymouth Malayalee Community Club (PMCC-UK)',
                'controller_contact' => 'privacy@pmcc-uk.org',
                'compiled_at'       => now()->toIso8601String(),
                'statutory_basis'   => 'UK GDPR Article 15 (Right of Access by the Data Subject)',
            ],
            'member_profile'            => $profile,
            'dependents'                => $children,
            'financial_history'         => $payments,
            'event_history'             => $bookings,
            'consent_records'           => $consents,
        ];
    }

    /**
     * Generate secure JSON and CSV document assets attached to a PrivacyRequest.
     */
    public static function generateExportDocuments(PrivacyRequest $request): array
    {
        $member = $request->member;
        if (!$member) {
            return [];
        }

        $data = self::compileMemberData($member);
        $reference = $request->reference_number;
        $timestamp = date('Ymd_His');

        $docDir = 'privacy_exports/' . $reference;
        Storage::disk('local')->makeDirectory($docDir);

        // 1. JSON Export
        $jsonFileName = "SAR_{$reference}_{$timestamp}.json";
        $jsonRelPath = "{$docDir}/{$jsonFileName}";
        Storage::disk('local')->put($jsonRelPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $jsonDoc = PrivacyRequestDocument::create([
            'privacy_request_id' => $request->id,
            'file_name'          => $jsonFileName,
            'file_path'          => $jsonRelPath,
            'file_type'          => 'export_json',
            'file_size'          => Storage::disk('local')->size($jsonRelPath),
            'download_token'     => Str::random(48),
            'token_expires_at'   => now()->addDays(7), // Expiring signed download
        ]);

        // 2. CSV Profile Export
        $csvFileName = "SAR_{$reference}_{$timestamp}.csv";
        $csvRelPath = "{$docDir}/{$csvFileName}";
        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
        fputcsv($handle, ['Category', 'Field', 'Value']);

        foreach ($data['member_profile'] as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $subK => $subV) {
                    fputcsv($handle, ['Member Profile', "{$k}.{$subK}", $subV]);
                }
            } else {
                fputcsv($handle, ['Member Profile', $k, (string) $v]);
            }
        }
        foreach ($data['dependents'] as $i => $child) {
            fputcsv($handle, ['Dependent #' . ($i + 1), 'Name', $child['name']]);
            fputcsv($handle, ['Dependent #' . ($i + 1), 'DOB', $child['dob']]);
            fputcsv($handle, ['Dependent #' . ($i + 1), 'Gender', $child['gender']]);
        }
        foreach ($data['financial_history'] as $i => $pay) {
            fputcsv($handle, ['Payment #' . ($i + 1), 'Type / Ref', $pay['payment_type'] . ' (' . $pay['transaction_ref'] . ')']);
            fputcsv($handle, ['Payment #' . ($i + 1), 'Amount (£)', $pay['amount_gbp']]);
            fputcsv($handle, ['Payment #' . ($i + 1), 'Date', $pay['date']]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('local')->put($csvRelPath, $csvContent);

        $csvDoc = PrivacyRequestDocument::create([
            'privacy_request_id' => $request->id,
            'file_name'          => $csvFileName,
            'file_path'          => $csvRelPath,
            'file_type'          => 'export_csv',
            'file_size'          => Storage::disk('local')->size($csvRelPath),
            'download_token'     => Str::random(48),
            'token_expires_at'   => now()->addDays(7),
        ]);

        return [$jsonDoc, $csvDoc];
    }
}
