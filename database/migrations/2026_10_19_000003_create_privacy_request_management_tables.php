<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('privacy_requests')) {
            Schema::create('privacy_requests', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number', 50)->unique();
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->string('requester_name', 150);
                $table->string('requester_email', 150);
                $table->string('requester_phone', 50)->nullable();
                $table->string('request_type', 50); // sar, rectification, erasure, restriction, objection, portability, consent_withdrawal, complaint
                $table->text('details')->nullable();
                $table->boolean('identity_verified')->default(false);
                $table->timestamp('identity_verified_at')->nullable();
                $table->string('identity_verification_method', 100)->nullable();
                $table->timestamp('received_at')->useCurrent();
                $table->date('statutory_deadline');
                $table->date('extended_deadline')->nullable();
                $table->text('extension_reason')->nullable();
                $table->string('status', 50)->default('submitted'); // submitted, identity_review, data_discovery, legal_review, approval_pending, fulfilment, completed, partially_fulfilled, refused, withdrawn, on_hold
                $table->string('decision', 50)->nullable(); // approved, partially_approved, refused, withdrawn
                $table->text('decision_reasoning')->nullable();
                $table->text('applicable_exemptions')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->string('correlation_id', 64)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('privacy_request_items')) {
            Schema::create('privacy_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('privacy_request_id')->constrained('privacy_requests')->cascadeOnDelete();
                $table->string('category', 60)->default('personal');
                $table->string('field_name', 100)->nullable();
                $table->text('current_value')->nullable();
                $table->text('proposed_value')->nullable();
                $table->string('status', 30)->default('pending'); // pending, accepted, rejected, exempted
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('privacy_request_actions')) {
            Schema::create('privacy_request_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('privacy_request_id')->constrained('privacy_requests')->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->string('action_type', 60);
                $table->text('description');
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('privacy_request_approvals')) {
            Schema::create('privacy_request_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('privacy_request_id')->constrained('privacy_requests')->cascadeOnDelete();
                $table->foreignId('approver_id')->constrained('admins')->cascadeOnDelete();
                $table->string('role_at_approval', 60);
                $table->string('approval_type', 60)->default('general'); // general, high_risk_erasure, bulk_export, complex_refusal
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->text('comments')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('privacy_request_documents')) {
            Schema::create('privacy_request_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('privacy_request_id')->constrained('privacy_requests')->cascadeOnDelete();
                $table->string('file_name', 200);
                $table->string('file_path', 300);
                $table->string('file_type', 60); // export_json, export_csv, export_pdf, member_proof, signed_response
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('download_token', 80)->nullable()->unique();
                $table->timestamp('token_expires_at')->nullable();
                $table->timestamp('downloaded_at')->nullable();
                $table->string('downloaded_ip', 45)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('privacy_request_status_history')) {
            Schema::create('privacy_request_status_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('privacy_request_id')->constrained('privacy_requests')->cascadeOnDelete();
                $table->string('from_status', 50)->nullable();
                $table->string('to_status', 50);
                $table->foreignId('changed_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_request_status_history');
        Schema::dropIfExists('privacy_request_documents');
        Schema::dropIfExists('privacy_request_approvals');
        Schema::dropIfExists('privacy_request_actions');
        Schema::dropIfExists('privacy_request_items');
        Schema::dropIfExists('privacy_requests');
    }
};
