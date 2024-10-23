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
        Schema::create('compartments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id')->unique();
            $table->string('internal_id')->nullable();
            $table->uuid('company_uuid')->index();
            $table->uuid('vehicle_uuid')->nullable()->index();
            $table->uuid('vendor_uuid')->nullable()->index();
            $table->uuid('order_uuid')->nullable()->index();
            $table->uuid('payload_uuid')->nullable()->index();
            $table->string('compartment_number')->nullable();
            $table->float('capacity_gal')->nullable();
            $table->float('filled_capacity_gal')->nullable();
            $table->json('acceptable_fuels')->nullable();
            $table->string('status')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compartments');
    }
};
