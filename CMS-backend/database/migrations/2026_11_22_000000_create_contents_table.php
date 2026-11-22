<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contents')) {
            Schema::create('contents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('category', 100)->nullable();
                $table->unsignedBigInteger('author_id');
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->timestamps();
                $table->index('author_id');
                $table->index('status');
                $table->index('category');
                $table->foreign('author_id', 'contents_author_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            });
        } else {
            $count = DB::table('contents')->count();

            if (!Schema::hasColumn('contents', 'id')) {
                throw new \RuntimeException(
                    'Cannot reconcile cms_db.contents: the required id column is missing.'
                );
            }

            if (DB::table('contents')->where('id', '<', 0)->exists()) {
                throw new \RuntimeException(
                    'Cannot reconcile cms_db.contents: id contains negative values that cannot become unsigned BIGINT.'
                );
            }

            Schema::table('contents', function (Blueprint $table) {
                $table->unsignedBigInteger('id', true)->change();
            });

            Schema::table('contents', function (Blueprint $table) {
                if (!Schema::hasColumn('contents', 'author_id') && $count === 0) {
                    $table->unsignedBigInteger('author_id');
                } elseif (!Schema::hasColumn('contents', 'author_id')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: the required author_id column is missing and the table contains records.'
                    );
                } elseif (DB::table('contents')->whereNull('author_id')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: author_id contains NULL values.'
                    );
                } elseif (DB::table('contents')->where('author_id', '<', 0)->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: author_id contains negative values.'
                    );
                } else {
                    $table->unsignedBigInteger('author_id')->nullable(false)->change();
                }
            });

            Schema::table('contents', function (Blueprint $table) {
                if (!Schema::hasColumn('contents', 'title') && $count === 0) {
                    $table->string('title');
                } elseif (!Schema::hasColumn('contents', 'title')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: the required title column is missing and the table contains records.'
                    );
                } elseif (DB::table('contents')->whereNull('title')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: title contains NULL values.'
                    );
                } elseif (DB::table('contents')->whereRaw('CHAR_LENGTH(title) > 255')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: title contains values longer than 255 characters.'
                    );
                } else {
                    $table->string('title')->nullable(false)->change();
                }

                if (!Schema::hasColumn('contents', 'body')) {
                    $table->text('body')->nullable();
                } else {
                    $table->text('body')->nullable()->change();
                }

                if (!Schema::hasColumn('contents', 'category')) {
                    $table->string('category', 100)->nullable();
                } elseif (DB::table('contents')->whereNotNull('category')->whereRaw('CHAR_LENGTH(category) > 100')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: category contains values longer than 100 characters.'
                    );
                } else {
                    $table->string('category', 100)->nullable()->change();
                }

                if (!Schema::hasColumn('contents', 'status') && $count === 0) {
                    $table->enum('status', ['draft', 'published'])->default('draft');
                } elseif (!Schema::hasColumn('contents', 'status')) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: the required status column is missing and the table contains records.'
                    );
                } elseif (DB::table('contents')->whereNull('status')->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: status contains NULL values.'
                    );
                } elseif (DB::table('contents')->whereNotIn('status', ['draft', 'published'])->exists()) {
                    throw new \RuntimeException(
                        'Cannot reconcile cms_db.contents: status contains values outside draft or published.'
                    );
                } else {
                    $table->enum('status', ['draft', 'published'])
                        ->default('draft')
                        ->nullable(false)
                        ->change();
                }

                if (!Schema::hasColumn('contents', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }

                if (!Schema::hasColumn('contents', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });

            foreach (['author_id', 'status', 'category'] as $column) {
                $columnHasIndex = collect(Schema::getIndexes('contents'))
                    ->contains(fn (array $index) => $index['columns'] === [$column]);

                if (!$columnHasIndex) {
                    Schema::table('contents', function (Blueprint $table) use ($column) {
                        $table->index($column);
                    });
                }
            }

            if ($count > 0) {
                $missingAuthors = DB::table('contents')
                    ->leftJoin('users', 'users.id', '=', 'contents.author_id')
                    ->whereNull('users.id')
                    ->count();

                if ($missingAuthors > 0) {
                    throw new \RuntimeException(
                        'Cannot add the contents author foreign key: existing contents reference missing users.'
                    );
                }
            }

            $foreignKeys = collect(Schema::getForeignKeys('contents'));
            $authorForeignKeys = $foreignKeys->filter(
                fn (array $foreignKey) => $foreignKey['columns'] === ['author_id']
            );
            $authorForeignKeyCorrect = $authorForeignKeys->contains(
                fn (array $foreignKey) =>
                    $foreignKey['foreign_table'] === 'users'
                    && $foreignKey['foreign_columns'] === ['id']
                    && $foreignKey['on_delete'] === 'restrict'
                    && $foreignKey['on_update'] === 'restrict'
            );

            foreach ($authorForeignKeys as $foreignKey) {
                $isCorrect = $foreignKey['foreign_table'] === 'users'
                    && $foreignKey['foreign_columns'] === ['id']
                    && $foreignKey['on_delete'] === 'restrict'
                    && $foreignKey['on_update'] === 'restrict';

                if (!$isCorrect) {
                    Schema::table('contents', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                }
            }

            if (!$authorForeignKeyCorrect) {
                Schema::table('contents', function (Blueprint $table) {
                    $table->foreign('author_id', 'contents_author_id_foreign')
                        ->references('id')
                        ->on('users')
                        ->restrictOnDelete()
                        ->restrictOnUpdate();
                });
            }
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Content reconciliation is irreversible. Restore a verified database backup instead of rolling it back.'
        );
    }
};
