<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('class_type', ['regular', 'private']);
            $table->foreignId('package_id')->constrained('packages')->onDelete('restrict');
            $table->string('parent_name', 100);
            $table->string('parent_phone', 20);
            $table->string('school', 100)->nullable();
            $table->string('school_grade', 20)->nullable()->comment('Contoh: SD Kelas 5, SMP Kelas 8');
            $table->string('subject', 100)->nullable()->comment('Mata pelajaran');
            $table->text('address')->nullable();
            $table->tinyInteger('due_day')->comment('Tanggal jatuh tempo 1-28');
            $table->date('join_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'cuti'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'class_type']);
            $table->index('parent_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
