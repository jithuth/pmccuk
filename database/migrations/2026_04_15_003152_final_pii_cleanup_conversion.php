<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renewal_requests', function (Blueprint $table) {
            $table->text('bank_account_holder')->nullable()->change();
            $table->text('transaction_ref')->nullable()->change();
            $table->text('payment_proof')->nullable()->change();
        });

        if (Schema::hasTable('admins')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->text('username')->nullable()->change();
            });
        }
    }

    public function down(): void { }
};
