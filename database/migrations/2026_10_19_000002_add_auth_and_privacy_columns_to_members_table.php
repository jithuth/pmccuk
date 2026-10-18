<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
            if (!Schema::hasColumn('members', 'remember_token')) {
                $table->rememberToken()->nullable()->after('password');
            }
            if (!Schema::hasColumn('members', 'privacy_anonymized_at')) {
                $table->timestamp('privacy_anonymized_at')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('members', 'privacy_hold')) {
                $table->boolean('privacy_hold')->default(false)->after('privacy_anonymized_at');
            }
            if (!Schema::hasColumn('members', 'privacy_notes')) {
                $table->text('privacy_notes')->nullable()->after('privacy_hold');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $drop = [];
            foreach (['password', 'remember_token', 'privacy_anonymized_at', 'privacy_hold', 'privacy_notes'] as $col) {
                if (Schema::hasColumn('members', $col)) {
                    $drop[] = $col;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
