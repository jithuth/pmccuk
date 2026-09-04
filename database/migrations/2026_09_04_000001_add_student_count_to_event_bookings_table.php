<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('event_bookings', 'student_count')) {
                $table->integer('student_count')->default(0)->after('infant_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('event_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('event_bookings', 'student_count')) {
                $table->dropColumn('student_count');
            }
        });
    }
};
