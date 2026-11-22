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
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 100);
                $table->string('email', 150)->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->enum('role', ['admin', 'teacher', 'student'])->default('student');
                $table->timestamps();
            });
        } else {
            $count = DB::table('users')->count();

            if (!Schema::hasColumn('users', 'id')) {
                throw new \RuntimeException(
                    'Cannot reconcile cms_db.users: the required id column is missing.'
                );
            }

            if (DB::table('users')->where('id', '<', 0)->exists()) {
                throw new \RuntimeException(
                    'Cannot reconcile cms_db.users: id contains negative values that cannot become unsigned BIGINT.'
                );
            }

            $duplicateEmails = DB::table('users')
                ->select('email')
                ->whereNotNull('email')
                ->groupBy('email')
                ->havingRaw('COUNT(*) > 1')
                ->limit(1)
                ->exists();

            if ($duplicateEmails) {
                throw new \RuntimeException(
                    'Cannot reconcile cms_db.users: email contains duplicate values.'
                );
            }

            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('id', true)->change();
            });

            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'name') && $count === 0) {
                    $table->string('name', 100);
                } elseif (!Schema::hasColumn('users', 'name')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: the required name column is missing and the table contains records.'
                    );
                } elseif (DB::table('users')->whereNull('name')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: name contains NULL values.'
                    );
                } elseif (DB::table('users')->whereRaw('CHAR_LENGTH(name) > 100')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: name contains values longer than 100 characters.'
                    );
                } else {
                    $table->string('name', 100)->nullable(false)->change();
                }

                if (!Schema::hasColumn('users', 'email') && $count === 0) {
                    $table->string('email', 150);
                } elseif (!Schema::hasColumn('users', 'email')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: the required email column is missing and the table contains records.'
                    );
                } elseif (DB::table('users')->whereNull('email')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: email contains NULL values.'
                    );
                } elseif (DB::table('users')->whereRaw('CHAR_LENGTH(email) > 150')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: email contains values longer than 150 characters.'
                    );
                } else {
                    $table->string('email', 150)->nullable(false)->change();
                }

                if (!Schema::hasColumn('users', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable();
                }

                if (!Schema::hasColumn('users', 'password') && $count === 0) {
                    $table->string('password');
                } elseif (!Schema::hasColumn('users', 'password')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: the required password column is missing and the table contains records.'
                    );
                } elseif (DB::table('users')->whereNull('password')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: password contains NULL values.'
                    );
                } else {
                    $table->string('password')->nullable(false)->change();
                }

                if (!Schema::hasColumn('users', 'remember_token')) {
                    $table->rememberToken();
                }

                if (!Schema::hasColumn('users', 'role') && $count === 0) {
                    $table->enum('role', ['admin', 'teacher', 'student'])->default('student');
                } elseif (!Schema::hasColumn('users', 'role')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: the required role column is missing and the table contains records.'
                    );
                } elseif (DB::table('users')->whereNull('role')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: role contains NULL values.'
                    );
                } elseif (DB::table('users')->whereNotIn('role', ['admin', 'teacher', 'student'])->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.users: role contains values outside admin, teacher, or student.'
                    );
                } else {
                    $table->enum('role', ['admin', 'teacher', 'student'])
                        ->default('student')
                        ->nullable(false)
                        ->change();
                }

                if (!Schema::hasColumn('users', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }

                if (!Schema::hasColumn('users', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });


            $emailHasUniqueIndex = collect(Schema::getIndexes('users'))
                ->contains(fn (array $index) => $index['unique']
                    && $index['columns'] === ['email']);

            if (!$emailHasUniqueIndex) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('email');
                });
            }
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email', 255)->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        } elseif (!Schema::hasColumns('password_reset_tokens', ['email', 'token', 'created_at'])) {
            throw new \RuntimeException(
                'cms_db.password_reset_tokens has an incompatible structure. Required columns are email, token, and created_at.'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new \RuntimeException(
            'The users reconciliation migration is irreversible. Restore a verified database backup instead of rolling it back.'
        );
    }
};
