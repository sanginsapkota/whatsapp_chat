<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->string('wa_id')->nullable();
            $table->string('wam_id')->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whatsapp_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_type')->default('text');
            $table->text('message_content')->nullable();
            $table->json('payload')->nullable();
            $table->string('direction');
            $table->string('status')->nullable();
            $table->timestamps();

            $table->index(['wa_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
