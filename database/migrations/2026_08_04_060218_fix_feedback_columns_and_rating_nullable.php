<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ------------------------------------------------------------
        // 1. Finish the "feedback" location columns (idempotent).
        //    An earlier migration already added some/all of these
        //    columns outside of Laravel's migration tracking, so we
        //    only add what's actually missing.
        // ------------------------------------------------------------
        Schema::table('feedback', function (Blueprint $table) {
            if (! Schema::hasColumn('feedback', 'city_id')) {
                $table->foreignId('city_id')
                    ->nullable()
                    ->after('window_id')
                    ->constrained('cities')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('feedback', 'subcity_id')) {
                $table->foreignId('subcity_id')
                    ->nullable()
                    ->after('city_id')
                    ->constrained('subcities')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('feedback', 'woreda_id')) {
                $table->foreignId('woreda_id')
                    ->nullable()
                    ->after('subcity_id')
                    ->constrained('woredas')
                    ->nullOnDelete();
            }
        });

        $indexExists = DB::table('pg_indexes')
            ->where('tablename', 'feedback')
            ->where('indexname', 'feedback_city_id_subcity_id_woreda_id_index')
            ->exists();

        if (! $indexExists) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->index(['city_id', 'subcity_id', 'woreda_id']);
            });
        }

        // Backfill from window location, in case the earlier partial
        // run never got to this step.
        DB::table('feedback')
            ->join('windows', 'windows.id', '=', 'feedback.window_id')
            ->update([
                'feedback.city_id' => DB::raw('windows.city_id'),
                'feedback.subcity_id' => DB::raw('windows.subcity_id'),
                'feedback.woreda_id' => DB::raw('windows.woreda_id'),
            ]);

        // ------------------------------------------------------------
        // 2. Make "customer_feedbacks.rating" nullable.
        //    A placeholder feedback row (token only, no rating yet) is
        //    created when the officer completes an application; the
        //    customer fills in the rating later via the feedback link.
        // ------------------------------------------------------------
        Schema::table('customer_feedbacks', function (Blueprint $table) {
            $table->enum('rating', [
                'very_satisfied',
                'satisfied',
                'not_satisfied',
                'other',
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_feedbacks', function (Blueprint $table) {
            $table->enum('rating', [
                'very_satisfied',
                'satisfied',
                'not_satisfied',
                'other',
            ])->nullable(false)->change();
        });

        Schema::table('feedback', function (Blueprint $table) {
            if (Schema::hasColumn('feedback', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }
            if (Schema::hasColumn('feedback', 'subcity_id')) {
                $table->dropConstrainedForeignId('subcity_id');
            }
            if (Schema::hasColumn('feedback', 'woreda_id')) {
                $table->dropConstrainedForeignId('woreda_id');
            }
        });
    }
};
