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
        // Retained as a historical migration filename. D-Tech CMS has no task module.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Genuine no-op; D-Tech CMS has no task module.
    }
};
