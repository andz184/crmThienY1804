<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the existing foreign key if it exists
            $table->dropForeign(['user_id']);

            // Change the column type to match users.id
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Add the foreign key constraint back
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['user_id']);

            // Change back to uuid
            $table->uuid('user_id')->nullable()->change();

            // Add back the original foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
