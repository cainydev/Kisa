<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_inspectors', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('label');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('bio_inspector_id')
                ->nullable()
                ->after('supplier_id')
                ->constrained()
                ->nullOnDelete();

            $table->dropColumn(['control_body', 'control_body_code']);
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bio_inspector_id');

            $table->string('control_body')->nullable()->after('operator_name');
            $table->string('control_body_code')->nullable()->after('control_body');
        });

        Schema::table('bio_inspectors', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
