<?php

use App\Models\RealEstate_Location;
use App\Models\User;
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
        Schema::create('real_estates', function (Blueprint $table) {
            $table->id();
            $table->float('latitude')->default(0);
            $table->float('longitude')->default(0);
            $table->enum('status',['closed', 'open'])->default('open');
            $table->enum('type',['rental', 'sale'])->default('sale');
            $table->integer('price');
            $table->enum('hidden',[1, 0])->default(0);
            $table->string('description');
            $table->integer('total_weight')->default(0);
            $table->enum('kind',['apartment', 'villa', 'chalet'])->default('apartment');
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->foreignId('real_estate_location_id')->constrained('real_estate_locations')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('real_estates');
    }
};
