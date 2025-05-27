<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiveSessionStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('live_session_stats')) {
            Schema::create('live_session_stats', function (Blueprint $table) {
                $table->id();
                $table->date('period_start')->index();
                $table->date('period_end')->index();
                $table->longText('stats_data'); // Lưu JSON dữ liệu thống kê
                $table->timestamps();

                // Tạo index compound key cho period_start + period_end
                $table->index(['period_start', 'period_end'], 'live_session_period_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('live_session_stats');
    }
}
