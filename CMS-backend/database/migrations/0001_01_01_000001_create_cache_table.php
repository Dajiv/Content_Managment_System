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
        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration')->index();
            });
        } elseif (!Schema::hasColumns('cache', ['key', 'value', 'expiration'])) {
            throw new \RuntimeException(
                'cms_db.cache has an incompatible structure. Required columns are key, value, and expiration.'
            );
        }

        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration')->index();
            });
        } elseif (!Schema::hasColumns('cache_locks', ['key', 'owner', 'expiration'])) {
            throw new \RuntimeException(
                'cms_db.cache_locks has an incompatible structure. Required columns are key, owner, and expiration.'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
