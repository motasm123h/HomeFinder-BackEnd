<?php

use App\Models\RealEstate;
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
        Schema::create('real_estate_properties', function (Blueprint $table) {
            $table->id();
            $table->enum('electricity_status', ['1', '2', '3']);
            $table->enum('water_status', ['1', '2', '3']);
            $table->enum('transportation_status', ['1', '2', '3']);
            $table->enum('water_well', ['1', '2']);
            $table->enum('solar_energy', ['1', '2']);
            $table->enum('garage', ['1', '2']);
            $table->integer('room_no');
            $table->enum('direction', ['1', '2', '3', '4']);
            $table->integer('space_status');
            $table->enum('elevator', ['1', '2']);
            $table->integer('floor');
            $table->enum('garden_status', ['1', '2']);
            $table->enum('attired', ['1', '2', '3']);
            $table->enum('ownership_type', ['green', 'court']);
            $table->foreignIdFor(RealEstate::class)->constrained()->onDelete('cascade');
            $table->integer('total_weight')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('real_estate_properties');
    }
};
