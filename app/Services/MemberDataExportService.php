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
            'guid'              => $member->guid,
            'membership_number' => $member->membership_id_assigned ?: 'PENDING',
            'prev_membership_no'=> $member->prev_membership_no,
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
            'photo_url'         => $member->photo_url,
            'family_photo_url'  => $member->family_photo_url,
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

        // 3. Payment Records (including initial membership fee & transaction history)
        $payments = [];
        if ($member->payment_amount > 0 || $member->payment_date || $member->bank_account_holder) {
            $payments[] = [
                'payment_id'          => 'INITIAL_MEMBERSHIP_FEE',
                'payment_type'        => 'Membership Registration / Annual Fee',
                'amount_gbp'          => (float) $member->payment_amount,
                'bank_account_holder' => $member->bank_account_holder,
                'transaction_ref'     => $member->transaction_ref,
                'payment_proof'       => $member->payment_proof ? url('img?p=' . ltrim($member->payment_proof, '/')) : null,
                'date'                => $member->payment_date ?: ($member->created_at ? $member->created_at->toIso8601String() : null),
            ];
        }

        foreach ($member->payments as $p) {
            $payments[] = [
                'payment_id'          => $p->id,
                'payment_type'        => $p->payment_type ?: 'Subscription / Fee Renewal',
                'amount_gbp'          => (float) $p->amount,
                'bank_account_holder' => $member->bank_account_holder,
                'transaction_ref'     => $p->transaction_ref,
                'payment_proof'       => null,
                'date'                => $p->created_at ? $p->created_at->toIso8601String() : null,
            ];
        }

        // 4. Event Bookings
        $bookings = \App\Models\EventBooking::where(function ($q) use ($member) {
                if ($member->email) {
                    $q->where('email', $member->email);
                }
                if ($member->membership_id_assigned) {
                    $q->orWhere('membership_no', $member->membership_id_assigned);
                }
                if ($member->mobile_number) {
                    $q->orWhere('phone', $member->mobile_number);
                }
            })
            ->with('event')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($b) {
                return [
                    'reference'      => $b->reference_no,
                    'event_title'    => $b->event->title ?? 'PMCC Event',
                    'event_date'     => $b->event->date ?? null,
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
                'granted'      => (bool) $c->granted,
                'granted_at'   => $c->granted_at ? $c->granted_at->toIso8601String() : null,
                'revoked_at'   => $c->revoked_at ? $c->revoked_at->toIso8601String() : null,
            ];
        })->toArray();

        return [
            'subject_access_disclosure' => [
                'data_controller'    => 'Plymouth Malayalee Community Club (PMCC-UK)',
                'controller_contact' => 'privacy@pmcc-uk.org',
                'compiled_at'        => now()->toIso8601String(),
                'statutory_basis'    => 'UK GDPR Article 15 (Right of Access by the Data Subject)',
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
            fputcsv($handle, ['Financial Payment #' . ($i + 1), 'Type / Description', $pay['payment_type']]);
            fputcsv($handle, ['Financial Payment #' . ($i + 1), 'Amount (£)', $pay['amount_gbp']]);
            fputcsv($handle, ['Financial Payment #' . ($i + 1), 'Transaction Ref', $pay['transaction_ref'] ?? 'N/A']);
            fputcsv($handle, ['Financial Payment #' . ($i + 1), 'Bank Account Holder', $pay['bank_account_holder'] ?? 'N/A']);
            fputcsv($handle, ['Financial Payment #' . ($i + 1), 'Payment Date', $pay['date'] ?? 'N/A']);
        }
        foreach ($data['event_history'] as $i => $ev) {
            fputcsv($handle, ['Event Booking #' . ($i + 1), 'Reference', $ev['reference']]);
            fputcsv($handle, ['Event Booking #' . ($i + 1), 'Event Title', $ev['event_title']]);
            fputcsv($handle, ['Event Booking #' . ($i + 1), 'Total Amount (£)', $ev['total_amount']]);
            fputcsv($handle, ['Event Booking #' . ($i + 1), 'Booking Status', $ev['booking_status']]);
            fputcsv($handle, ['Event Booking #' . ($i + 1), 'Attended / Checked-in', $ev['attended'] ? 'YES (' . $ev['check_in_time'] . ')' : 'NO']);
        }
        foreach ($data['consent_records'] as $i => $c) {
            fputcsv($handle, ['Consent Record #' . ($i + 1), $c['purpose'], ($c['granted'] ? 'GRANTED' : 'REVOKED') . ' (Basis: ' . $c['lawful_basis'] . ')']);
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
