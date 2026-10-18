<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('data_retention_policies')) {
            Schema::create('data_retention_policies', function (Blueprint $table) {
                $table->id();
                $table->string('data_category', 80)->unique();
                $table->string('purpose', 200);
                $table->string('lawful_basis', 100);
                $table->unsignedInteger('retention_period_days');
                $table->string('retention_trigger', 80); // created_at, membership_expiry, event_date, request_closed
                $table->string('responsible_owner', 100)->default('Privacy Officer');
                $table->string('disposal_action', 50)->default('anonymize'); // anonymize, soft_delete, hard_delete, archive
                $table->boolean('legal_hold')->default(false);
                $table->text('legal_hold_reason')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('data_retention_logs')) {
            Schema::create('data_retention_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('policy_id')->nullable()->constrained('data_retention_policies')->nullOnDelete();
                $table->string('execution_type', 40)->default('automated'); // automated, manual
                $table->unsignedInteger('records_evaluated')->default(0);
                $table->unsignedInteger('records_disposed')->default(0);
                $table->string('disposal_action', 50);
                $table->text('summary')->nullable();
                $table->integer('executed_by')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('privacy_notices')) {
            Schema::create('privacy_notices', function (Blueprint $table) {
                $table->id();
                $table->string('version', 20)->unique();
                $table->string('title', 200);
                $table->longText('content_markdown');
                $table->text('summary_of_changes')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_consents')) {
            Schema::create('member_consents', function (Blueprint $table) {
                $table->id();
                $table->integer('member_id')->index();
                $table->foreignId('privacy_notice_id')->nullable()->constrained('privacy_notices')->nullOnDelete();
                $table->string('consent_key', 80); // marketing_emails, whatsapp_community, photo_publication, directory_listing
                $table->string('lawful_basis', 80)->default('consent');
                $table->boolean('granted')->default(false);
                $table->timestamp('granted_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->unique(['member_id', 'consent_key']);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_consents');
        Schema::dropIfExists('privacy_notices');
        Schema::dropIfExists('data_retention_logs');
        Schema::dropIfExists('data_retention_policies');
    }
};
