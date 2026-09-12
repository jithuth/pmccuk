<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_scheduled_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('audience'); // members, attendees, students, executives, custom
            $table->json('selected_executives')->nullable();
            $table->longText('custom_numbers')->nullable();
            $table->longText('message');
            $table->json('attachments')->nullable(); // images, documents, urls, contacts
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed, cancelled
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->longText('error_log')->nullable();
            $table->dateTime('executed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_scheduled_broadcasts');
    }
};
