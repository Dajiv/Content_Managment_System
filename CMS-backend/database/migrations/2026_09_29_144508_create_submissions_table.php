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
        if (!Schema::hasTable('submissions')) {
            Schema::create('submissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('assignment_id');
                $table->unsignedBigInteger('student_id');
                $table->string('file_path');
                $table->string('grade', 10)->nullable();
                $table->text('feedback')->nullable();
                $table->timestamp('submitted_at')->useCurrent();
                $table->timestamps();
                $table->index('assignment_id');
                $table->index('student_id');
                $table->foreign('assignment_id', 'submissions_assignment_id_foreign')
                    ->references('id')
                    ->on('assignments')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
                $table->foreign('student_id', 'submissions_student_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            });
        } else {
            $count = DB::table('submissions')->count();

            if (!Schema::hasColumn('submissions', 'id')) {
                throw new \RuntimeException(
                    'Cannot reconcile cms_db.submissions: the required id column is missing.'
                );
            }

            if (DB::table('submissions')->where('id', '<', 0)->exists()) {
                throw new \RuntimeException(
                    'Cannot reconcile cms_db.submissions: id contains negative values that cannot become unsigned BIGINT.'
                );
            }

            Schema::table('submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('id', true)->change();
            });

            Schema::table('submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('submissions', 'assignment_id') && $count === 0) {
                    $table->unsignedBigInteger('assignment_id');
                } elseif (!Schema::hasColumn('submissions', 'assignment_id')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: the required assignment_id column is missing and the table contains records.'
                    );
                } elseif (DB::table('submissions')->whereNull('assignment_id')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: assignment_id contains NULL values.'
                    );
                } elseif (DB::table('submissions')->where('assignment_id', '<', 0)->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: assignment_id contains negative values.'
                    );
                } else {
                    $table->unsignedBigInteger('assignment_id')->nullable(false)->change();
                }
            });

            Schema::table('submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('submissions', 'student_id') && $count === 0) {
                    $table->unsignedBigInteger('student_id');
                } elseif (!Schema::hasColumn('submissions', 'student_id')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: the required student_id column is missing and the table contains records.'
                    );
                } elseif (DB::table('submissions')->whereNull('student_id')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: student_id contains NULL values.'
                    );
                } elseif (DB::table('submissions')->where('student_id', '<', 0)->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: student_id contains negative values.'
                    );
                } else {
                    $table->unsignedBigInteger('student_id')->nullable(false)->change();
                }
            });

            Schema::table('submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('submissions', 'file_path') && $count === 0) {
                    $table->string('file_path');
                } elseif (!Schema::hasColumn('submissions', 'file_path')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: the required file_path column is missing and the table contains records.'
                    );
                } elseif (DB::table('submissions')->whereNull('file_path')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: file_path contains NULL values.'
                    );
                } else {
                    $table->string('file_path')->nullable(false)->change();
                }

                if (!Schema::hasColumn('submissions', 'grade')) {
                    $table->string('grade', 10)->nullable();
                } elseif (DB::table('submissions')->whereNotNull('grade')->whereRaw('CHAR_LENGTH(grade) > 10')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: grade contains values longer than 10 characters.'
                    );
                } else {
                    $table->string('grade', 10)->nullable()->change();
                }

                if (!Schema::hasColumn('submissions', 'feedback')) {
                    $table->text('feedback')->nullable();
                } else {
                    $table->text('feedback')->nullable()->change();
                }

                if (!Schema::hasColumn('submissions', 'submitted_at') && $count === 0) {
                    $table->timestamp('submitted_at')->useCurrent();
                } elseif (!Schema::hasColumn('submissions', 'submitted_at')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: the required submitted_at column is missing and the table contains records.'
                    );
                } elseif (DB::table('submissions')->whereNull('submitted_at')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.submissions: submitted_at contains NULL values.'
                    );
                } else {
                    $table->timestamp('submitted_at')->useCurrent()->nullable(false)->change();
                }

                if (!Schema::hasColumn('submissions', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }

                if (!Schema::hasColumn('submissions', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });

            foreach (['assignment_id', 'student_id'] as $column) {
                $columnHasIndex = collect(Schema::getIndexes('submissions'))
                    ->contains(fn (array $index) => $index['columns'] === [$column]);

                if (!$columnHasIndex) {
                    Schema::table('submissions', function (Blueprint $table) use ($column) {
                        $table->index($column);
                    });
                }
            }

            if ($count > 0) {
                $missingAssignments = DB::table('submissions')
                    ->leftJoin('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                    ->whereNull('assignments.id')
                    ->count();

                if ($missingAssignments > 0) {
                    throw new \RuntimeException(
                        'Cannot add the submissions assignment foreign key: existing submissions reference missing assignments.'
                    );
                }

                $missingStudents = DB::table('submissions')
                    ->leftJoin('users', 'users.id', '=', 'submissions.student_id')
                    ->whereNull('users.id')
                    ->count();

                if ($missingStudents > 0) {
                    throw new \RuntimeException(
                        'Cannot add the submissions student foreign key: existing submissions reference missing users.'
                    );
                }
            }

            $foreignKeys = collect(Schema::getForeignKeys('submissions'));

            $assignmentForeignKeys = $foreignKeys->filter(
                fn (array $foreignKey) => $foreignKey['columns'] === ['assignment_id']
            );
            $assignmentForeignKeyCorrect = $assignmentForeignKeys->contains(
                fn (array $foreignKey) =>
                    $foreignKey['foreign_table'] === 'assignments'
                    && $foreignKey['foreign_columns'] === ['id']
                    && $foreignKey['on_delete'] === 'restrict'
                    && $foreignKey['on_update'] === 'restrict'
            );

            foreach ($assignmentForeignKeys as $foreignKey) {
                $isCorrect = $foreignKey['foreign_table'] === 'assignments'
                    && $foreignKey['foreign_columns'] === ['id']
                    && $foreignKey['on_delete'] === 'restrict'
                    && $foreignKey['on_update'] === 'restrict';

                if (!$isCorrect) {
                    Schema::table('submissions', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                }
            }

            if (!$assignmentForeignKeyCorrect) {
                Schema::table('submissions', function (Blueprint $table) {
                    $table->foreign('assignment_id', 'submissions_assignment_id_foreign')
                        ->references('id')
                        ->on('assignments')
                        ->restrictOnDelete()
                        ->restrictOnUpdate();
                });
            }

            $studentForeignKeys = $foreignKeys->filter(
                fn (array $foreignKey) => $foreignKey['columns'] === ['student_id']
            );
            $studentForeignKeyCorrect = $studentForeignKeys->contains(
                fn (array $foreignKey) =>
                    $foreignKey['foreign_table'] === 'users'
                    && $foreignKey['foreign_columns'] === ['id']
                    && $foreignKey['on_delete'] === 'restrict'
                    && $foreignKey['on_update'] === 'restrict'
            );

            foreach ($studentForeignKeys as $foreignKey) {
                $isCorrect = $foreignKey['foreign_table'] === 'users'
                    && $foreignKey['foreign_columns'] === ['id']
                    && $foreignKey['on_delete'] === 'restrict'
                    && $foreignKey['on_update'] === 'restrict';

                if (!$isCorrect) {
                    Schema::table('submissions', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                }
            }

            if (!$studentForeignKeyCorrect) {
                Schema::table('submissions', function (Blueprint $table) {
                    $table->foreign('student_id', 'submissions_student_id_foreign')
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
            'Submission reconciliation is irreversible. Restore a verified database backup instead of rolling it back.'
        );
    }
};
