<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->string('step_key')->unique();
            $table->string('step_name');
            $table->text('description')->nullable();
            $table->json('next_steps')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_flow_steps');
    }
};
