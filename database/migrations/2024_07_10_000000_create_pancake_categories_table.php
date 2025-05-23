<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pancake_categories', function (Blueprint $table) {
            $table->id();
            $table->string('pancake_id')->unique()->comment('ID from Pancake');
            $table->string('name');
            $table->string('pancake_parent_id')->nullable()->comment('Parent ID from Pancake');
            // $table->foreignId('parent_id')->nullable()->constrained('pancake_categories')->onDelete('set null'); // For local hierarchy
            $table->integer('level')->nullable()->comment('Category level from Pancake');
            $table->string('status')->nullable()->comment('Status from Pancake');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->json('api_response')->nullable()->comment('Full API response for the category');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pancake_categories');
    }
}; 