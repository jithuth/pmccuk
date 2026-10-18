<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('security_audit_logs')) {
            Schema::create('security_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_type', 40)->default('admin'); // admin, member, system, guest
                $table->string('actor_username', 100)->nullable();
                $table->string('action', 100);
                $table->string('resource_type', 80)->nullable();
                $table->string('resource_id', 80)->nullable();
                $table->string('correlation_id', 64)->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('justification')->nullable();
                $table->string('outcome', 30)->default('success'); // success, denied, failed
                $table->json('payload_summary')->nullable();
                $table->string('approval_reference', 60)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('security_incidents')) {
            Schema::create('security_incidents', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number', 50)->unique();
                $table->string('title', 200);
                $table->string('severity', 30)->default('medium'); // low, medium, high, critical
                $table->string('status', 30)->default('open'); // open, investigating, contained, remediated, closed
                $table->json('affected_data_categories')->nullable();
                $table->unsignedInteger('estimated_affected_members')->default(0);
                $table->timestamp('detected_at')->useCurrent();
                $table->integer('reported_by')->nullable()->index();
                $table->integer('lead_investigator')->nullable()->index();
                $table->text('containment_actions')->nullable();
                $table->text('root_cause_analysis')->nullable();
                $table->text('remediation_steps')->nullable();
                $table->boolean('ico_notifiable')->default(false);
                $table->text('ico_assessment_reasoning')->nullable();
                $table->timestamp('ico_notified_at')->nullable();
                $table->string('ico_reference_number', 80)->nullable();
                $table->boolean('members_notified')->default(false);
                $table->timestamp('members_notified_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('security_incident_timeline')) {
            Schema::create('security_incident_timeline', function (Blueprint $table) {
                $table->id();
                $table->foreignId('security_incident_id')->constrained('security_incidents')->cascadeOnDelete();
                $table->integer('recorded_by')->nullable()->index();
                $table->string('event_type', 60); // detection, containment, investigation, notification, remediation
                $table->text('summary');
                $table->timestamp('event_timestamp')->useCurrent();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_incident_timeline');
        Schema::dropIfExists('security_incidents');
        Schema::dropIfExists('security_audit_logs');
    }
};
