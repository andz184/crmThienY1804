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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->after('password'); // ID team mà user thuộc về
            $table->unsignedBigInteger('manages_team_id')->nullable()->after('team_id'); // ID team mà user quản lý (nếu là leader)

            // Nếu bạn muốn có bảng `teams` riêng, hãy tạo FK.
            // Tạm thời chỉ dùng integer.
            // $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
            // $table->foreign('manages_team_id')->references('id')->on('teams')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Bỏ comment FK drops nếu bạn tạo FK
            // $table->dropForeign(['team_id']);
            // $table->dropForeign(['manages_team_id']);
            $table->dropColumn(['team_id', 'manages_team_id']);
        });
    }
};
