<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up()
    {
        try {
            // 1. Standardize orders table
            if (Schema::hasTable('orders')) {
                Schema::table('orders', function (Blueprint $table) {
                    // Remove old columns if they exist
                    if (Schema::hasColumn('orders', 'live_session_id')) {
                        $table->dropColumn('live_session_id');
                    }
                    if (Schema::hasColumn('orders', 'live_session_date')) {
                        $table->dropColumn('live_session_date');
                    }

                    // Keep only live_session_info JSON column
                    if (!Schema::hasColumn('orders', 'live_session_info')) {
                        $table->json('live_session_info')->nullable()->comment('Structured information about live session')->after('notes');
                    }
                });
            }

            // 2. Drop old live_session_reports table if exists
            Schema::dropIfExists('live_session_reports');

            // 3. Update live_session_stats table structure
            if (Schema::hasTable('live_session_stats')) {
                Schema::table('live_session_stats', function (Blueprint $table) {
                    // Add new columns if they don't exist
                    if (!Schema::hasColumn('live_session_stats', 'period_start')) {
                        $table->date('period_start')->index();
                    }
                    if (!Schema::hasColumn('live_session_stats', 'period_end')) {
                        $table->date('period_end')->index();
                    }
                    if (!Schema::hasColumn('live_session_stats', 'delivering_orders')) {
                        $table->integer('delivering_orders')->default(0);
                    }
                    if (!Schema::hasColumn('live_session_stats', 'products_data')) {
                        $table->json('products_data')->nullable();
                    }
                    if (!Schema::hasColumn('live_session_stats', 'customers_data')) {
                        $table->json('customers_data')->nullable();
                    }

                    // Add indexes if they don't exist
                    if (!Schema::hasIndex('live_session_stats', 'unique_live_session')) {
                        try {
                            $table->unique(['live_number', 'session_date'], 'unique_live_session');
                        } catch (\Exception $e) {
                            // Index might already exist with a different name
                        }
                    }
                    if (!Schema::hasIndex('live_session_stats', 'period_live_index')) {
                        try {
                            $table->index(['period_start', 'period_end', 'live_number'], 'period_live_index');
                        } catch (\Exception $e) {
                            // Index might already exist with a different name
                        }
                    }
                });
            }
        } catch (\Exception $e) {
            // Log error but don't throw it
            \Log::error('Error in standardize_live_session_tables migration: ' . $e->getMessage());
        }
    }

    public function down()
    {
        try {
            // Restore original structure if needed
            if (Schema::hasTable('orders')) {
                Schema::table('orders', function (Blueprint $table) {
                    if (!Schema::hasColumn('orders', 'live_session_id')) {
                        $table->integer('live_session_id')->nullable()->after('campaign_id');
                    }
                    if (!Schema::hasColumn('orders', 'live_session_date')) {
                        $table->date('live_session_date')->nullable()->after('live_session_id');
                    }
                });
            }

            // Drop indexes from live_session_stats if they exist
            if (Schema::hasTable('live_session_stats')) {
                Schema::table('live_session_stats', function (Blueprint $table) {
                    try {
                        $table->dropIndex('unique_live_session');
                    } catch (\Exception $e) {}
                    try {
                        $table->dropIndex('period_live_index');
                    } catch (\Exception $e) {}
                });
            }
        } catch (\Exception $e) {
            // Log error but don't throw it
            \Log::error('Error in standardize_live_session_tables migration rollback: ' . $e->getMessage());
        }
    }
};
