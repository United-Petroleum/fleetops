<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('compartments', function (Blueprint $table) {
            // Ensure vehicle_uuid is nullable
            $table->uuid('vehicle_uuid')->nullable()->change();

            // Check and add foreign key constraints if they don't exist
            $this->addForeignKeyIfNotExists($table, 'company_uuid', 'companies', 'uuid', 'CASCADE', 'CASCADE');
            $this->addForeignKeyIfNotExists($table, 'vehicle_uuid', 'vehicles', 'uuid', 'SET NULL', 'CASCADE');
            $this->addForeignKeyIfNotExists($table, 'vendor_uuid', 'vendors', 'uuid', 'SET NULL', 'CASCADE');
            $this->addForeignKeyIfNotExists($table, 'current_job_uuid', 'orders', 'uuid', 'SET NULL', 'CASCADE');

            // Check if the unique constraint exists before trying to drop it
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('compartments');
            if (array_key_exists('compartments_vehicle_uuid_unique', $indexesFound)) {
                $table->dropUnique(['vehicle_uuid']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compartments', function (Blueprint $table) {
            // Drop foreign key constraints if they exist
            $this->dropForeignKeyIfExists($table, 'compartments_company_uuid_foreign');
            $this->dropForeignKeyIfExists($table, 'compartments_vehicle_uuid_foreign');
            $this->dropForeignKeyIfExists($table, 'compartments_vendor_uuid_foreign');
            $this->dropForeignKeyIfExists($table, 'compartments_current_job_uuid_foreign');

            // If you want to revert vehicle_uuid to non-nullable in down(), uncomment the next line
            // $table->uuid('vehicle_uuid')->nullable(false)->change();

            // If you want to add back a unique constraint in down(), uncomment the next line
            // $table->unique('vehicle_uuid');
        });
    }

    private function addForeignKeyIfNotExists(Blueprint $table, $column, $referencedTable, $referencedColumn, $onDelete = null, $onUpdate = null)
    {
        $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys($table->getTable());
        $foreignKeyName = $table->getTable() . '_' . $column . '_foreign';

        $exists = collect($foreignKeys)->contains(function ($fk) use ($foreignKeyName) {
            return $fk->getName() === $foreignKeyName;
        });

        if (!$exists) {
            $table->foreign($column)
                  ->references($referencedColumn)
                  ->on($referencedTable)
                  ->onDelete($onDelete)
                  ->onUpdate($onUpdate);
        }
    }

    private function dropForeignKeyIfExists(Blueprint $table, $foreignKeyName)
    {
        $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys($table->getTable());
        
        $exists = collect($foreignKeys)->contains(function ($fk) use ($foreignKeyName) {
            return $fk->getName() === $foreignKeyName;
        });

        if ($exists) {
            $table->dropForeign($foreignKeyName);
        }
    }
};
