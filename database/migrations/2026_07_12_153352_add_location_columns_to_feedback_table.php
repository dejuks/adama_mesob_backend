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
        Schema::table('feedback', function (Blueprint $table) {
            //
            $table->foreignId('city_id')
                ->nullable()
                ->after('service_id')
                ->constrained('cities')
                ->nullOnDelete();

            $table->foreignId('subcity_id')
                ->nullable()
                ->after('city_id')
                ->constrained('subcities')
                ->nullOnDelete();

            $table->foreignId('woreda_id')
                ->nullable()
                ->after('subcity_id')
                ->constrained('woredas')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {

            $table->dropForeign(['city_id']);
            $table->dropForeign(['subcity_id']);
            $table->dropForeign(['woreda_id']);

            $table->dropColumn([
                'city_id',
                'subcity_id',
                'woreda_id'
            ]);

        });
    }
};
