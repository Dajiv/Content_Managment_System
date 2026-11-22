<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'role')) {
            $count = DB::table('users')->count();
            if ($count > 0) {
                throw new \RuntimeException(
                    'Cannot add cms_db.users.role: the table is populated and no role value can be safely invented.'
                );
            }

            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'teacher', 'student'])
                    ->default('student')
                    ->after('password');
            });
        }

        if (DB::table('users')->whereNull('role')->exists()) {
            throw new \RuntimeException(
                'Cannot reconcile cms_db.users.role: existing role values contain NULL.'
            );
        }

        if (DB::table('users')->whereNotIn('role', ['admin', 'teacher', 'student'])->exists()) {
            throw new \RuntimeException(
                'Cannot reconcile cms_db.users.role: existing values are outside admin, teacher, or student.'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new \RuntimeException(
            'The role reconciliation migration is irreversible. Restore a verified database backup instead of rolling it back.'
        );
    }
};
