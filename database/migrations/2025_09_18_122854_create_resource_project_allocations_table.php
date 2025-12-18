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
        Schema::create('resource_project_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resource_id')->nullable()->default(null);
            $table->foreign('resource_id')->references('id')->on('resources')->onDelete('cascade');
            $table->unsignedBigInteger('project_id')->nullable()->default(null);
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            // $table->string('month')->nullable(); // YYYY-MM
            // $table->json('month')->nullable()->default(null);
            // $table->decimal('total_hours', 8, 2)->default(0.00);
            // $table->decimal('available_hours', 5, 2)->default(0.00);
            // $table->decimal('allocated_hours', 5, 2)->default(0.00);
            $table->json('months_and_hours')->nullable(); 
            $table->json('daily_hours')->nullable(); 
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['resource_id', 'project_id'], 'unique_resource_project_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resource_project_allocations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
