<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Hapus foreign key constraint terlebih dahulu
            $table->dropForeign(['student_id']);
            // Hapus index composite sebelum drop column
            $table->dropIndex(['student_id', 'day_of_week']);
            // Hapus kolom student_id
            $table->dropColumn('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('id')->constrained('students')->onDelete('cascade');
        });
    }
};
