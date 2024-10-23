<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payloads', function (Blueprint $table) {
            $table->uuid('compartment_uuid')->nullable()->after('type');
            $table->foreign('compartment_uuid')->references('uuid')->on('compartments')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('payloads', function (Blueprint $table) {
            $table->dropForeign(['compartment_uuid']);
            $table->dropColumn('compartment_uuid');
        });
    }
};