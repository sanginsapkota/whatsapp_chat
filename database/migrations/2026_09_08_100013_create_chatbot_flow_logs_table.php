<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_flow_logs', function (Blueprint $table) {
            $table->id();
            $table->string('wa_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whatsapp_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('step_key');
            $table->string('next_step_key')->nullable();
            $table->json('input_data')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_flow_logs');
    }
};
