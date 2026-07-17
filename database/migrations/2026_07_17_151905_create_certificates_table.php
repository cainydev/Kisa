<?php

use App\Models\Supplier;
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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Supplier::class)->constrained()->cascadeOnDelete();
            $table->string('certificate_number')->nullable();
            $table->string('operator_name')->nullable();
            $table->string('control_body')->nullable();
            $table->string('control_body_code')->nullable();
            $table->string('activities')->nullable();
            $table->string('product_categories')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->date('issued_at')->nullable();
            $table->string('issued_place')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
