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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type')->nullable();      // เช่น ASSIGN_TASK, INVITE, OVERDUE
            $table->string('title');                 // หัวข้อ
            $table->text('body')->nullable();        // รายละเอียด
            $table->string('url')->nullable();       // ลิงก์ไปหน้า board/task/activity
            $table->json('data')->nullable();        // เผื่อเก็บข้อมูลเพิ่ม

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
