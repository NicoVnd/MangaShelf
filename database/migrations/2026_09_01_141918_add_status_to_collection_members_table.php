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
        Schema::table('collection_members', function (Blueprint $table) {
            $table->string('status')->default('accepted')->after('role'); // 'pending', 'accepted', 'declined'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_members', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
