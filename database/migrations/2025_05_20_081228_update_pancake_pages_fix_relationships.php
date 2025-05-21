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
        Schema::table('pancake_pages', function (Blueprint $table) {
            // Ensure pancake_shop_table_id exists for foreign key relationship
            if (!Schema::hasColumn('pancake_pages', 'pancake_shop_table_id')) {
                $table->unsignedBigInteger('pancake_shop_table_id')->nullable()->after('pancake_page_id');

                // Add foreign key constraint if pancake_shops table exists
                if (Schema::hasTable('pancake_shops')) {
                    $table->foreign('pancake_shop_table_id')
                          ->references('id')
                          ->on('pancake_shops')
                          ->onDelete('set null');
                }
            }

            // Add shop_id field for direct reference to Pancake's shop_id
            if (!Schema::hasColumn('pancake_pages', 'shop_id')) {
                $table->string('shop_id')->nullable()->after('pancake_shop_table_id');
            }

            // Add platform field if it doesn't exist
            if (!Schema::hasColumn('pancake_pages', 'platform')) {
                $table->string('platform')->nullable()->after('name');
            }

            // Add name field if it doesn't exist
            if (!Schema::hasColumn('pancake_pages', 'name')) {
                $table->string('name')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pancake_pages', function (Blueprint $table) {
            // Only drop foreign key constraint if it exists
            if (Schema::hasColumn('pancake_pages', 'pancake_shop_table_id')) {
                $foreignKeys = $this->listTableForeignKeys('pancake_pages');
                $foreignKeyName = null;

                foreach ($foreignKeys as $key) {
                    if (str_contains($key, 'pancake_shop_table_id')) {
                        $foreignKeyName = $key;
                        break;
                    }
                }

                if ($foreignKeyName) {
                    $table->dropForeign($foreignKeyName);
                }
            }

            // Only drop the columns if they were added in this migration
            // We don't want to drop existing columns
        });
    }

    /**
     * Get a list of foreign keys for a table
     *
     * @param string $table
     * @return array
     */
    private function listTableForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();

        return array_map(
            function($key) {
                return $key->getName();
            },
            $conn->listTableForeignKeys($table)
        );
    }
};
