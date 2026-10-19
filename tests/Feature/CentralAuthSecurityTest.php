<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuthEvent;
use App\Models\Member;
use App\Models\OtpChallenge;
use App\Models\SecurityPolicy;
use App\Models\UserSession;
use App\Services\Auth\CentralAuthService;
use App\Services\Auth\DeviceSessionService;
use App\Services\Auth\MfaService;
use App\Services\Auth\OtpService;
use Database\Seeders\PrivacyGovernanceSeeder;
use Database\Seeders\SecurityPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class CentralAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(PrivacyGovernanceSeeder::class);
        $this->seed(SecurityPolicySeeder::class);
    }

    private function createTestMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'guid' => 'test-mem-guid-' . uniqid(),
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'mobile_number' => '+447900112233',
            'membership_id_assigned' => 'PMCC-9901',
            'status' => 'active',
            'membership_type' => 'individual',
            'two_factor_enabled' => false,
        ], $attributes));
    }

    private function createTestAdmin(array $attributes = []): Admin
    {
        return Admin::create(array_merge([
            'username' => 'secadmin_' . uniqid(),
            'email' => 'secadmin@pmcc-uk.org',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'admin',
            'two_factor_enabled' => false,
        ], $attributes));
    }

    /**
     * Test 1: OTP Generation and Expiry
     */
    public function test_otp_generation_and_expiry(): void
    {
        $member = $this->createTestMember();

        $res = $this->postJson(route('member.login.otp.send'), [
            'identifier' => 'PMCC-9901'
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        // Assert challenge created with hashed OTP (never plaintext in DB)
        $challenge = OtpChallenge::where('member_id', $member->id)->latest()->first();
        $this->assertNotNull($challenge);
        $this->assertNotEmpty($challenge->otp_hash);
        $this->assertNotEquals('123456', $challenge->otp_hash);
        $this->assertTrue($challenge->expires_at->isFuture());

        // Fast-forward expiry
        $challenge->update(['expires_at' => now()->subMinutes(1)]);

        // Attempt verification with expired challenge
        $verifyRes = $this->postJson(route('member.login.otp.verify'), [
            'otp' => '123456',
            'challenge_uuid' => $challenge->challenge_uuid
        ]);

        $verifyRes->assertStatus(422);
        $verifyRes->assertJsonFragment(['success' => false]);
    }

    /**
     * Test 2: OTP Replay Prevention (Single Use)
     */
    public function test_otp_replay_prevention(): void
    {
        $member = $this->createTestMember();

        // Create challenge with known hash
        $knownOtp = '882211';
        $challenge = OtpChallenge::create([
            'identifier' => 'PMCC-9901',
            'member_id' => $member->id,
            'otp_hash' => Hash::make($knownOtp),
            'purpose' => 'member_login',
            'channel' => 'both',
            'delivery_status' => 'confirmed',
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
            'ip_address' => '127.0.0.1',
        ]);

        // First verification succeeds
        $res1 = $this->postJson(route('member.login.otp.verify'), [
            'otp' => $knownOtp,
            'challenge_uuid' => $challenge->challenge_uuid
        ]);
        $res1->assertStatus(200);
        $res1->assertJson(['success' => true]);

        // Challenge is consumed
        $challenge->refresh();
        $this->assertTrue($challenge->isConsumed());

        // Log out user so guest:member middleware allows subsequent challenge verification attempt
        Auth::guard('member')->logout();

        // Replay attempt fails immediately
        $res2 = $this->postJson(route('member.login.otp.verify'), [
            'otp' => $knownOtp,
            'challenge_uuid' => $challenge->challenge_uuid
        ]);
        $res2->assertStatus(422);
        $res2->assertJsonFragment(['success' => false]);
    }

    /**
     * Test 3: OTP Throttling and Resend Cooldown
     */
    public function test_otp_throttling_and_resend_cooldown(): void
    {
        $member = $this->createTestMember();

        // Request 1
        $res1 = $this->postJson(route('member.login.otp.send'), [
            'identifier' => 'PMCC-9901'
        ]);
        $res1->assertStatus(200);

        // Immediate Request 2 should hit cooldown
        $res2 = $this->postJson(route('member.login.otp.send'), [
            'identifier' => 'PMCC-9901'
        ]);
        $res2->assertStatus(429);
        $res2->assertJsonFragment(['success' => false]);
    }

    /**
     * Test 4: Member Enumeration Resistance
     */
    public function test_member_enumeration_resistance(): void
    {
        // Request with non-existent member
        $res = $this->postJson(route('member.login.otp.send'), [
            'identifier' => 'NON_EXISTENT_ID_99999'
        ]);

        $res->assertStatus(200);
        // Generic enumeration-safe response
        $res->assertJsonFragment([
            'success' => true,
            'message' => 'If an active account exists with the provided information, a verification code has been dispatched.'
        ]);
    }

    /**
     * Test 5: MFA Enrollment Flow
     */
    public function test_mfa_enrollment_flow(): void
    {
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        // Initiate MFA
        $initRes = $this->postJson(route('member.security.mfa.initiate'));
        $initRes->assertStatus(200);
        $initRes->assertJsonStructure(['success', 'secret', 'qr_image_url']);

        $secret = $initRes->json('secret');
        $google2fa = new Google2FA();
        $validCode = $google2fa->getCurrentOtp($secret);

        // Confirm MFA with valid TOTP code
        $confirmRes = $this->postJson(route('member.security.mfa.confirm'), [
            'code' => $validCode
        ]);

        $confirmRes->assertStatus(200);
        $confirmRes->assertJsonStructure(['success', 'recovery_codes']);
        $this->assertCount(8, $confirmRes->json('recovery_codes'));

        $member->refresh();
        $this->assertTrue($member->two_factor_enabled);
        $this->assertNotNull($member->two_factor_secret);
        $this->assertNotNull($member->two_factor_confirmed_at);
    }

    /**
     * Test 6: MFA Login Enforcement
     */
    public function test_mfa_login_enforcement(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $member = $this->createTestMember([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        // Create valid primary OTP challenge
        $otp = '771122';
        $challenge = OtpChallenge::create([
            'identifier' => 'PMCC-9901',
            'member_id' => $member->id,
            'otp_hash' => Hash::make($otp),
            'purpose' => 'member_login',
            'channel' => 'both',
            'delivery_status' => 'confirmed',
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
            'ip_address' => '127.0.0.1',
        ]);

        // Submit primary OTP
        $otpRes = $this->postJson(route('member.login.otp.verify'), [
            'otp' => $otp,
            'challenge_uuid' => $challenge->challenge_uuid
        ]);

        $otpRes->assertStatus(200);
        $otpRes->assertJson([
            'success' => true,
            'mfa_required' => true,
            'redirect' => route('member.mfa.challenge')
        ]);

        // Assert member is NOT yet authenticated in guard
        $this->assertFalse(Auth::guard('member')->check());

        // Verify with valid TOTP code
        $totp = $google2fa->getCurrentOtp($secret);
        $mfaRes = $this->post(route('member.mfa.verify'), ['code' => $totp]);

        $mfaRes->assertRedirect(route('member.privacy.index'));
        $this->assertTrue(Auth::guard('member')->check());
        $this->assertEquals($member->id, Auth::guard('member')->id());
    }

    /**
     * Test 7: MFA Bypass Prevention
     */
    public function test_mfa_bypass_prevention(): void
    {
        $member = $this->createTestMember([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        ]);

        // Establish pending MFA state
        Session::put('pending_mfa_guard', 'member');
        Session::put('pending_mfa_member_id', $member->id);

        // Attempt direct access to protected privacy center
        $res = $this->get('/member/privacy');
        $res->assertRedirect(route('member.mfa.challenge'));
    }

    /**
     * Test 8: Recovery Code Single-Use
     */
    public function test_recovery_code_single_use(): void
    {
        $mfaService = app(MfaService::class);
        $secret = 'JBSWY3DPEHPK3PXP';
        $recoveryCode = 'ABCD-1234';

        $member = $this->createTestMember([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => json_encode([[
                'hash' => Hash::make($recoveryCode),
                'used' => false,
                'used_at' => null
            ]])
        ]);

        // First verification with recovery code succeeds
        $res1 = $mfaService->verifyChallenge($member, 'member', $recoveryCode);
        $this->assertTrue($res1['success']);

        // Recovery code is marked used
        $member->refresh();
        $codes = json_decode($member->two_factor_recovery_codes, true);
        $this->assertTrue($codes[0]['used']);

        // Second verification with same recovery code fails
        $res2 = $mfaService->verifyChallenge($member, 'member', $recoveryCode);
        $this->assertFalse($res2['success']);
    }

    /**
     * Test 9: Session Fixation Prevention
     */
    public function test_session_fixation_prevention(): void
    {
        $member = $this->createTestMember();

        \Illuminate\Support\Facades\Session::start();
        $initialSessionId = \Illuminate\Support\Facades\Session::getId();

        // Complete login
        $centralAuth = app(CentralAuthService::class);
        $centralAuth->establishMemberSession($member, request());

        $newSessionId = \Illuminate\Support\Facades\Session::getId();
        $this->assertNotEquals($initialSessionId, $newSessionId);
    }

    /**
     * Test 10: Session Revocation
     */
    public function test_session_revocation(): void
    {
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        // Create active session
        $userSession = DeviceSessionService::createSessionRecord('member', $member->id, 'member');
        $this->assertTrue($userSession->is_active);

        // Revoke session
        DeviceSessionService::revokeSession($userSession->id, 'member', $member->id);

        $userSession->refresh();
        $this->assertFalse($userSession->is_active);
        $this->assertNotNull($userSession->revoked_at);
    }

    /**
     * Test 11: Audit Event Generation
     */
    public function test_audit_event_generation(): void
    {
        $member = $this->createTestMember();

        // Request OTP
        $this->postJson(route('member.login.otp.send'), [
            'identifier' => 'PMCC-9901'
        ]);

        $event = AuthEvent::where('actor_identifier', 'PMCC-9901')
            ->where('action', 'otp_requested')
            ->first();

        $this->assertNotNull($event);
        $this->assertEquals('otp', $event->event_category);
        $this->assertEquals('success', $event->status);
        $this->assertNotNull($event->event_uuid);
    }

    /**
     * Test 12: Unauthorized Security Dashboard Access
     */
    public function test_unauthorized_security_dashboard_access(): void
    {
        // Unauthenticated visitor
        $res = $this->get(route('admin.security.overview'));
        $res->assertRedirect(route('admin.login'));

        // Authenticated Member should NOT access Admin Security Centre
        $member = $this->createTestMember();
        $resMember = $this->actingAs($member, 'member')->get(route('admin.security.overview'));
        $resMember->assertRedirect(route('admin.login'));
    }

    /**
     * Test 13: Privileged Admin Mandatory MFA
     */
    public function test_admin_mandatory_mfa_enforcement(): void
    {
        $admin = $this->createTestAdmin(['two_factor_enabled' => false]);

        $res = $this->post('/tomjsailor', [
            'username' => $admin->username,
            'password' => 'AdminPass123!'
        ]);

        // When admin mandatory MFA is enabled and admin has no 2FA, redirect directly to 2FA setup
        $res->assertRedirect(route('admin.2fa.setup'));
    }

    /**
     * Test 14: Security Policy Seeder and Defaults Verification
     */
    public function test_security_policy_seeder_and_defaults(): void
    {
        $this->seed(\Database\Seeders\SecurityPolicySeeder::class);

        $this->assertEquals(5, \App\Models\SecurityPolicy::get('otp_expiry_minutes'));
        $this->assertEquals(60, \App\Models\SecurityPolicy::get('otp_resend_cooldown_seconds'));
        $this->assertEquals(5, \App\Models\SecurityPolicy::get('otp_max_attempts'));
        $this->assertTrue(\App\Models\SecurityPolicy::get('admin_mandatory_mfa'));
    }
}
