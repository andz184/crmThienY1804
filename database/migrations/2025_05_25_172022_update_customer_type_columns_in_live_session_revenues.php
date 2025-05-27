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
        Schema::table('live_session_revenues', function (Blueprint $table) {
            if (!Schema::hasColumn('live_session_revenues', 'new_customers')) {
                $table->integer('new_customers')->default(0);
            }
            if (!Schema::hasColumn('live_session_revenues', 'returning_customers')) {
                $table->integer('returning_customers')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_session_revenues', function (Blueprint $table) {
            $table->dropColumn(['new_customers', 'returning_customers']);
        });
    }
};
