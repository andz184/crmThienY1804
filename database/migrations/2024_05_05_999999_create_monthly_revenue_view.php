<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW monthly_revenue AS
            SELECT user_id, YEAR(created_at) as year, MONTH(created_at) as month,
                   SUM(COALESCE(total_value, 0)) as total
            FROM orders
            WHERE status = 'completed'
            GROUP BY user_id, year, month
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS monthly_revenue');
    }
};
