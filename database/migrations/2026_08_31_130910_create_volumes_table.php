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
        Schema::create('volumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->string('number', 20)->index();
            $table->string('title')->nullable();
            $table->string('isbn10', 20)->nullable()->index();
            $table->string('isbn13', 20)->nullable()->index();
            $table->date('publication_date')->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->string('cover_url')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volumes');
    }
};
