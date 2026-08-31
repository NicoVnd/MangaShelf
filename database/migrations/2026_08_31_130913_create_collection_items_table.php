<?php

use App\Enums\CollectionItemStatus;
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
        Schema::create('collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignId('volume_id')->constrained('volumes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default(CollectionItemStatus::Owned->value);
            $table->decimal('purchase_price', 8, 2)->nullable();
            $table->date('purchased_at')->nullable();
            $table->date('read_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['collection_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_items');
    }
};
