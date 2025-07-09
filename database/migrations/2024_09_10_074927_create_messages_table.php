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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id');
            $table->foreignId('receiver_id');
            $table->text('text')->nullable();
            $table->string('attachment')->nullable();
            $table->double('attachment_size')->nullable();
            $table->foreignId('replied_to')->nullable();
            $table->enum('status',['sent','delivered','read','not_delivered'])->default('sent');
            $table->enum('is_forwarded',['0','1'])->default('0')->nullable();
            $table->enum('is_deleted',['0','1'])->default('0')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
