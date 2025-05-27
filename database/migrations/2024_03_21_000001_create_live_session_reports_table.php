<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('live_session_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->string('report_type'); // daily, monthly, yearly
            $table->json('report_data');
            $table->timestamp('last_calculated_at');
            $table->timestamps();

            // Indexes
            $table->index('report_date');
            $table->index('report_type');
            $table->unique(['report_date', 'report_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('live_session_reports');
    }
};
