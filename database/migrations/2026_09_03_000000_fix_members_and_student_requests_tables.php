<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix members table membership_type & marital_status columns
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if (Schema::hasColumn('members', 'membership_type')) {
                    $table->text('membership_type')->nullable()->change();
                } else {
                    $table->text('membership_type')->nullable();
                }

                if (Schema::hasColumn('members', 'marital_status')) {
                    $table->text('marital_status')->nullable()->change();
                } else {
                    $table->text('marital_status')->nullable();
                }
            });
        }

        // 2. Fix renewal_requests table membership_type column
        if (Schema::hasTable('renewal_requests')) {
            Schema::table('renewal_requests', function (Blueprint $table) {
                if (Schema::hasColumn('renewal_requests', 'membership_type')) {
                    $table->text('membership_type')->nullable()->change();
                } else {
                    $table->text('membership_type')->nullable();
                }
            });
        }

        // 3. Fix student_requests table columns
        if (!Schema::hasTable('student_requests')) {
            Schema::create('student_requests', function (Blueprint $table) {
                $table->id();
                $table->text('full_name')->nullable();
                $table->text('email')->nullable();
                $table->text('phone')->nullable();
                $table->text('university')->nullable();
                $table->text('study_year')->nullable();
                $table->string('status', 50)->default('pending');
                $table->timestamps();
            });
        } else {
            Schema::table('student_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('student_requests', 'full_name')) {
                    $table->text('full_name')->nullable();
                } else {
                    $table->text('full_name')->nullable()->change();
                }

                if (!Schema::hasColumn('student_requests', 'email')) {
                    $table->text('email')->nullable();
                } else {
                    $table->text('email')->nullable()->change();
                }

                if (!Schema::hasColumn('student_requests', 'phone')) {
                    $table->text('phone')->nullable();
                } else {
                    $table->text('phone')->nullable()->change();
                }

                if (!Schema::hasColumn('student_requests', 'university')) {
                    $table->text('university')->nullable();
                } else {
                    $table->text('university')->nullable()->change();
                }

                if (!Schema::hasColumn('student_requests', 'study_year')) {
                    $table->text('study_year')->nullable();
                } else {
                    $table->text('study_year')->nullable()->change();
                }

                if (!Schema::hasColumn('student_requests', 'status')) {
                    $table->string('status', 50)->default('pending');
                }
            });
        }
    }

    public function down(): void
    {
    }
};
