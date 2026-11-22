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
        if (!Schema::hasTable('assignments')) {
            Schema::create('assignments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('teacher_id');
                $table->date('due_date');
                $table->timestamps();
                $table->index('teacher_id');
                $table->foreign('teacher_id', 'assignments_teacher_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            });
        } else {
            $count = DB::table('assignments')->count();

            if (!Schema::hasColumn('assignments', 'id')) {
                throw new \RuntimeException(
                    'Cannot reconcile cms_db.assignments: the required id column is missing.'
                );
            }

            if (DB::table('assignments')->where('id', '<', 0)->exists()) {
                throw new \RuntimeException(
                    'Cannot reconcile cms_db.assignments: id contains negative values that cannot become unsigned BIGINT.'
                );
            }

            Schema::table('assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('id', true)->change();
            });

            Schema::table('assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('assignments', 'teacher_id') && $count === 0) {
                    $table->unsignedBigInteger('teacher_id');
                } elseif (!Schema::hasColumn('assignments', 'teacher_id')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.assignments: the required teacher_id column is missing and the table contains records.'
                    );
                } elseif (DB::table('assignments')->whereNull('teacher_id')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.assignments: teacher_id contains NULL values.'
                    );
                } elseif (DB::table('assignments')->where('teacher_id', '<', 0)->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.assignments: teacher_id contains negative values.'
                    );
                } else {
                    $table->unsignedBigInteger('teacher_id')->nullable(false)->change();
                }
            });

            Schema::table('assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('assignments', 'title') && $count === 0) {
                    $table->string('title');
                } elseif (!Schema::hasColumn('assignments', 'title')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.assignments: the required title column is missing and the table contains records.'
                    );
                } elseif (DB::table('assignments')->whereNull('title')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.assignments: title contains NULL values.'
                    );
                } elseif (DB::table('assignments')->whereRaw('CHAR_LENGTH(title) > 255')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.assignments: title contains values longer than 255 characters.'
                    );
                } else {
                    $table->string('title')->nullable(false)->change();
                }

                if (!Schema::hasColumn('assignments', 'description')) {
                    $table->text('description')->nullable();
                } else {
                    $table->text('description')->nullable()->change();
                }

                if (!Schema::hasColumn('assignments', 'due_date') && $count === 0) {
                    $table->date('due_date');
                } elseif (!Schema::hasColumn('assignments', 'due_date')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.assignments: the required due_date column is missing and the table contains records.'
                    );
                } elseif (DB::table('assignments')->whereNull('due_date')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.assignments: due_date contains NULL values.'
                    );
                } else {
                    $table->date('due_date')->nullable(false)->change();
                }

                if (!Schema::hasColumn('assignments', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }

                if (!Schema::hasColumn('assignments', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });

            $teacherHasIndex = collect(Schema::getIndexes('assignments'))
                ->contains(fn (array $index) => $index['columns'] === ['teacher_id']);

            if (!$teacherHasIndex) {
                Schema::table('assignments', function (Blueprint $table) {
                    $table->index('teacher_id');
                });
            }

            if ($count > 0) {
                $missingTeachers = DB::table('assignments')
                    ->leftJoin('users', 'users.id', '=', 'assignments.teacher_id')
                    ->whereNull('users.id')
                    ->count();

                if ($missingTeachers > 0) {
                    throw new \RuntimeException(
                        'Cannot add the assignments teacher foreign key: existing assignments reference missing users.'
                    );
                }
            }

            $foreignKeys = collect(Schema::getForeignKeys('assignments'));
            $matchingForeignKeys = $foreignKeys->filter(
                fn (array $foreignKey) => $foreignKey['columns'] === ['teacher_id']
            );
            $correctForeignKey = $matchingForeignKeys->contains(
                fn (array $foreignKey) =>
                    $foreignKey['foreign_table'] === 'users'
                    && $foreignKey['foreign_columns'] === ['id']
                    && $foreignKey['on_delete'] === 'restrict'
                    && $foreignKey['on_update'] === 'restrict'
            );

            foreach ($matchingForeignKeys as $foreignKey) {
                $isCorrect = $foreignKey['foreign_table'] === 'users'
                    && $foreignKey['foreign_columns'] === ['id']
                    && $foreignKey['on_delete'] === 'restrict'
                    && $foreignKey['on_update'] === 'restrict';

                if (!$isCorrect) {
                    Schema::table('assignments', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                }
            }

            if (!$correctForeignKey) {
                Schema::table('assignments', function (Blueprint $table) {
                    $table->foreign('teacher_id', 'assignments_teacher_id_foreign')
                        ->references('id')
                        ->on('users')
                        ->restrictOnDelete()
                        ->restrictOnUpdate();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new \RuntimeException(
            'Assignment reconciliation is irreversible. Restore a verified database backup instead of rolling it back.'
        );
    }
};
