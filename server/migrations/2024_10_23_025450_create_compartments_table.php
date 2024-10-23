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
            $table->uuid('current_job_uuid')->nullable()->index();
            $table->string('auth_token')->nullable();
            $table->string('signup_token_used')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('compartment_number')->nullable();
            $table->integer('capacity')->nullable();
            $table->json('acceptable_fuels')->nullable();
            $table->point('location')->nullable();
            $table->string('heading')->nullable();
            $table->string('bearing')->nullable();
            $table->string('altitude')->nullable();
            $table->string('speed')->nullable();
            $table->string('country')->nullable();
            $table->string('currency')->nullable();
            $table->string('city')->nullable();
            $table->boolean('online')->default(false);
            $table->string('current_status')->nullable();
            $table->string('slug')->nullable();
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
