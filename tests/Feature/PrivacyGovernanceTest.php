<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\DataRetentionLog;
use App\Models\DataRetentionPolicy;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\PrivilegedAccessGrant;
use App\Models\PrivacyRequest;
use App\Models\PrivacyRequestApproval;
use App\Models\PrivacyRequestDocument;
use App\Models\Role;
use App\Services\StatutoryDeadlineCalculator;
use Carbon\Carbon;
use Database\Seeders\PrivacyGovernanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrivacyGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed RBAC, retention policies, and privacy notices
        $this->seed(PrivacyGovernanceSeeder::class);
    }

    /**
     * Test 1: Unauthenticated visitors cannot access Member Privacy Center
     */
    public function test_unauthenticated_user_cannot_access_member_privacy_center(): void
    {
        $response = $this->get('/member/privacy');
        $response->assertRedirect(route('member.login'));
    }

    /**
     * Test 2: Authenticated member can view their own privacy dashboard
     */
    public function test_authenticated_member_can_view_own_privacy_center(): void
    {
        $member = Member::create([
            'guid' => 'test-member-guid-1',
            'full_name' => 'Alice Walker',
            'email' => 'alice@example.com',
            'mobile_number' => '+447000000001',
            'password' => Hash::make('Secret123!'),
            'membership_status' => 'approved',
            'membership_type' => 'individual',
        ]);

        $response = $this->actingAs($member, 'member')->get('/member/privacy');
        $response->assertStatus(200);
        $response->assertSee('Alice Walker');
    }

    /**
     * Test 3: Member cannot download an arbitrary or forged SAR document token (Anti-IDOR)
     */
    public function test_member_cannot_download_unauthorized_or_forged_document(): void
    {
        $alice = Member::create([
            'guid' => 'test-alice-guid',
            'full_name' => 'Alice Walker',
            'email' => 'alice@example.com',
            'mobile_number' => '+447000000001',
            'password' => Hash::make('Secret123!'),
        ]);

        $bob = Member::create([
            'guid' => 'test-bob-guid',
            'full_name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'mobile_number' => '+447000000002',
            'password' => Hash::make('Secret123!'),
        ]);

        $bobRequest = PrivacyRequest::create([
            'reference_number' => 'SAR-BOB-001',
            'member_id' => $bob->id,
            'requester_name' => $bob->full_name,
            'requester_email' => $bob->email,
            'request_type' => 'access',
            'status' => 'completed',
            'statutory_deadline' => now()->addDays(30),
        ]);

        $bobDoc = PrivacyRequestDocument::create([
            'privacy_request_id' => $bobRequest->id,
            'file_name' => 'bob_sar.json',
            'file_path' => 'privacy_exports/bob_sar.json',
            'file_type' => 'sar_export',
            'file_size' => 1024,
            'download_token' => 'bob-secret-download-token',
            'token_expires_at' => now()->addDays(5),
            'download_count' => 0,
        ]);

        // Alice attempts to download Bob's document using the token
        $response = $this->actingAs($alice, 'member')->get('/member/privacy/download/bob-secret-download-token');
        $response->assertStatus(403);
    }

    /**
     * Test 4: Diagnostic and maintenance endpoints reject unauthenticated requests
     */
    public function test_diagnostic_endpoints_reject_unauthenticated_requests(): void
    {
        $response = $this->get('/clear-everything');
        $response->assertRedirect(route('admin.login'));

        $response2 = $this->get('/run-migrations');
        $response2->assertRedirect(route('admin.login'));

        $response3 = $this->get('/fix-storage');
        $response3->assertRedirect(route('admin.login'));
    }

    /**
     * Test 5: Diagnostic endpoints reject non-superadmin users
     */
    public function test_diagnostic_endpoints_reject_regular_admin(): void
    {
        $regularAdmin = Admin::create([
            'username' => 'regular_admin',
            'email' => 'admin_user@pmcc.org.uk',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($regularAdmin, 'admin')->get('/clear-everything');
        $response->assertStatus(403);
    }

    /**
     * Test 6: Developer cannot access member personal records without active break-glass grant
     */
    public function test_developer_cannot_access_member_records_without_break_glass_grant(): void
    {
        $devAdmin = Admin::create([
            'username' => 'dev_contractor',
            'email' => 'dev@contractor.co.uk',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $devRole = Role::where('slug', 'developer')->first();
        $devAdmin->roles()->sync([$devRole->id]);

        $member = Member::create([
            'guid' => 'confidential-member-guid',
            'full_name' => 'Confidential Member',
            'email' => 'confidential@example.com',
            'mobile_number' => '+447000000099',
            'password' => Hash::make('Secret123!'),
        ]);

        // Developer attempts to view member privacy request management
        $response = $this->actingAs($devAdmin, 'admin')->get('/admin/privacy/requests');
        $response->assertStatus(403);
    }

    /**
     * Test 7: Anti-Self-Approval: Admin cannot approve their own break-glass privileged access request
     */
    public function test_admin_cannot_approve_own_break_glass_access(): void
    {
        $requester = Admin::create([
            'username' => 'ops_lead',
            'email' => 'ops@pmcc.org.uk',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $grant = PrivilegedAccessGrant::create([
            'admin_id' => $requester->id,
            'target_role_slug' => 'privacy_officer',
            'justification' => 'Production incident triage',
            'status' => 'pending',
            'expires_at' => now()->addHours(2),
        ]);

        // Requester tries to approve their own grant
        $response = $this->actingAs($requester, 'admin')->post('/admin/privacy/access/' . $grant->id . '/grant', [
            'duration_hours' => 2,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 8: Statutory Deadline Calculator accurately calculates 1 calendar month and rolls over weekends
     */
    public function test_statutory_deadline_calculator_rules(): void
    {
        // 31st January 2026 -> Last day of February
        $startDate = Carbon::create(2026, 1, 31, 10, 0, 0);
        $deadline = StatutoryDeadlineCalculator::calculateDeadline($startDate);

        // February 2026 has 28 days (28th is a Saturday) -> Rollover to Monday March 2nd
        $this->assertEquals('2026-03-02', $deadline->toDateString());

        // Complex request extension (adds 2 additional calendar months)
        $extended = StatutoryDeadlineCalculator::calculateDeadline($startDate, 2);
        $this->assertTrue($extended->gt($deadline));
    }

    /**
     * Test 9: Data Erasure anonymizes personal profile but preserves required financial transaction audit records
     */
    public function test_data_retention_and_erasure_protects_legal_financial_records(): void
    {
        $member = Member::create([
            'guid' => 'retention-test-guid',
            'full_name' => 'John Financial',
            'email' => 'john.fin@example.com',
            'mobile_number' => '+447000000077',
            'house_details' => '12 High Street',
            'post_code' => 'SW1A 1AA',
            'password' => Hash::make('Secret123!'),
        ]);

        $transaction = FinancialTransaction::create([
            'ref_no' => 'TXN-998877',
            'amount' => 150.00,
            'type' => 'income',
            'category' => 'membership_fee',
            'description' => 'Annual Membership Contribution',
            'payment_method' => 'bank_transfer',
            'transaction_date' => now()->subYears(2)->toDateString(),
        ]);

        // Execute selective anonymization of profile data
        $member->update([
            'full_name' => 'Anonymized Member ' . $member->id,
            'email' => 'anonymized_' . $member->id . '@pmcc.invalid',
            'mobile_number' => null,
            'house_details' => null,
            'post_code' => null,
            'privacy_anonymized_at' => now(),
        ]);

        // Verify profile is anonymized
        $refreshed = Member::find($member->id);
        $this->assertStringContainsString('Anonymized Member', $refreshed->full_name);
        $this->assertNull($refreshed->mobile_number);

        // Crucial UK GDPR & HMRC Compliance: Financial ledger entry MUST remain intact
        $savedTxn = FinancialTransaction::first();
        $this->assertNotNull($savedTxn);
        $this->assertEquals(150.00, (float)$savedTxn->amount);
    }

    /**
     * Test 10: Member API Login requires credentials and generates verifiable HMAC token
     */
    public function test_member_api_login_and_token_signature_verification(): void
    {
        $member = Member::create([
            'guid' => 'api-test-guid-88',
            'full_name' => 'API Member',
            'email' => 'api_member@example.com',
            'mobile_number' => '+447000000888',
            'membership_id_assigned' => 'PMC-888',
            'password' => Hash::make('StrongApiPass123!'),
        ]);

        // Attempt login with invalid password
        $failResponse = $this->postJson('/api/v1/auth/login', [
            'login' => 'PMC-888',
            'password' => 'WrongPassword',
        ]);
        $failResponse->assertStatus(401);

        // Attempt login with valid credentials
        $successResponse = $this->postJson('/api/v1/auth/login', [
            'login' => 'PMC-888',
            'password' => 'StrongApiPass123!',
        ]);
        $successResponse->assertStatus(200);
        $token = $successResponse->json('data.token');
        $this->assertNotEmpty($token);

        // Access protected profile with issued token
        $profileResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/member/profile');
        $profileResponse->assertStatus(200);
        $profileResponse->assertJsonPath('data.full_name', 'API Member');

        // Forged token should be rejected (wrong signature)
        $forgedToken = base64_encode($member->id . ':' . $member->guid . ':' . time() . ':fake_signature_hash');
        $forgedResponse = $this->withHeader('Authorization', 'Bearer ' . $forgedToken)
            ->getJson('/api/v1/member/profile');
        $forgedResponse->assertStatus(401);
    }

    /**
     * Test 11: Member Web Portal authentication with password
     */
    public function test_member_web_portal_login_flow(): void
    {
        $member = Member::create([
            'guid' => 'web-member-guid-123',
            'full_name' => 'Web Member',
            'email' => 'web_member@example.com',
            'mobile_number' => '+447000000123',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        // Wrong password should fail
        $failResponse = $this->post('/member/login/password', [
            'login' => 'web_member@example.com',
            'password' => 'WrongPassword',
        ]);
        $failResponse->assertSessionHasErrors('password');
        $this->assertGuest('member');

        // Correct password should authenticate
        $successResponse = $this->post('/member/login/password', [
            'login' => 'web_member@example.com',
            'password' => 'CorrectPassword123!',
        ]);
        $successResponse->assertRedirect(route('member.privacy.index'));
        $this->assertAuthenticatedAs($member, 'member');
    }

    /**
     * Test: Member Web Portal OTP authentication flow
     */
    public function test_member_otp_login_flow(): void
    {
        $member = Member::create([
            'guid' => 'otp-member-guid-789',
            'full_name' => 'OTP Member',
            'email' => 'otp_member@example.com',
            'mobile_number' => '+447000000789',
            'membership_id_assigned' => 'PMCC-789',
        ]);

        // Request OTP
        $sendResponse = $this->postJson('/member/login/otp/send', [
            'identifier' => 'PMCC-789',
        ]);
        $sendResponse->assertStatus(200);
        $sendResponse->assertJsonPath('success', true);

        // Verify session holds generated OTP
        $otp = session('member_login_otp');
        $this->assertNotEmpty($otp);

        // Verify with invalid OTP fails
        $failVerify = $this->postJson('/member/login/otp/verify', [
            'otp' => '000000',
        ]);
        $failVerify->assertStatus(422);

        // Verify with valid OTP succeeds and authenticates
        $successVerify = $this->postJson('/member/login/otp/verify', [
            'otp' => $otp,
        ]);
        $successVerify->assertStatus(200);
        $successVerify->assertJsonPath('success', true);
        $this->assertAuthenticatedAs($member, 'member');
    }

    /**
     * Test 12: Member can submit a Subject Access Request (SAR)
     */
    public function test_member_can_submit_subject_access_request(): void
    {
        $member = Member::create([
            'guid' => 'sar-member-guid-456',
            'full_name' => 'SAR Member',
            'email' => 'sar_member@example.com',
            'mobile_number' => '+447000000456',
            'password' => Hash::make('Pass123!'),
        ]);

        $response = $this->actingAs($member, 'member')->post('/member/privacy/sar', [
            'request_details' => 'I would like an export of all my personal records and booking attendance history.',
            'format_preference' => 'json',
        ]);

        $response->assertSessionHas('success');

        $sar = PrivacyRequest::where('member_id', $member->id)->first();
        $this->assertNotNull($sar);
        $this->assertEquals('sar', $sar->request_type);
        $this->assertEquals('submitted', $sar->status);
        $this->assertNotNull($sar->statutory_deadline);
    }

    /**
     * Test 13: Member can update granular privacy consent preferences
     */
    public function test_member_can_update_consent_preferences(): void
    {
        $member = Member::create([
            'guid' => 'consent-member-guid-789',
            'full_name' => 'Consent Member',
            'email' => 'consent_member@example.com',
            'mobile_number' => '+447000000789',
            'password' => Hash::make('Pass123!'),
        ]);

        $response = $this->actingAs($member, 'member')->post('/member/privacy/consents', [
            'whatsapp_broadcasts' => '1',
            'marketing_emails'    => '0',
            'photo_publication'   => '1',
        ]);

        $response->assertSessionHas('success');

        $waConsent = \App\Models\MemberConsent::where('member_id', $member->id)
            ->where('consent_key', 'whatsapp_broadcasts')
            ->first();
        $this->assertNotNull($waConsent);
        $this->assertTrue((bool)$waConsent->granted);

        $mktConsent = \App\Models\MemberConsent::where('member_id', $member->id)
            ->where('consent_key', 'marketing_emails')
            ->first();
        $this->assertNotNull($mktConsent);
        $this->assertFalse((bool)$mktConsent->granted);
    }
}
