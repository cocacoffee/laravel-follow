<?php

/*
 * This file is part of the overtrue/laravel-follow
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\LaravelFollow\Traits;

use Illuminate\Support\Facades\DB;
use Overtrue\LaravelFollow\Follow;

/**
 * Trait CanBlock.
 */
trait CanBlock
{
    /**
     * Block an item or items.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                        $class
     *
     * @throws \Exception
     *
     * @return array
     */
    public function block($targets, $class = __CLASS__)
    {
        return Follow::attachRelations($this, 'blocks', $targets, $class);
    }

    /**
     * UnBlock an item or items.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                        $class
     *
     * @return array
     */
    public function unBlock($targets, $class = __CLASS__)
    {
        return Follow::detachRelations($this, 'blocks', $targets, $class);
    }

    /**
     * Toggle block an item or items.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                        $class
     *
     * @throws \Exception
     *
     * @return array
     */
    public function toggleBlock($targets, $class = __CLASS__)
    {
        return Follow::toggleRelations($this, 'blocks', $targets, $class);
    }

    /**
     * Check if user is blocking given item.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $target
     * @param string                                        $class
     *
     * @return bool
     */
    public function isBlocking($target, $class = __CLASS__)
    {
        return Follow::isRelationExists($this, 'blocks', $target, $class);
    }

    /**
     * Check if user and target user is blocking each other.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $target
     * @param string                                        $class
     *
     * @return bool
     */
    public function areBlockingEachOther($target, $class = __CLASS__)
    {
        return Follow::isRelationExists($this, 'blocks', $target, $class) && Follow::isRelationExists($target, 'blocks', $this, $class);
    }

    /**
     * Return item blockings.
     *
     * @param string $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function blocks($class = __CLASS__)
    {
        $table = config('follow.followable_table');
        $foreignKey = config('follow.users_table_foreign_key', 'user_id');
        $targetTable = (new $class())->getTable();
        $tablePrefixedForeignKey = app('db.connection')->getQueryGrammar()->wrap(\sprintf('pivot_followables.%s', $foreignKey));
        $eachOtherKey = app('db.connection')->getQueryGrammar()->wrap('pivot_each_other');

        return $this->morphedByMany($class, config('follow.morph_prefix'), $table)
                    ->wherePivot('relation', '=', Follow::RELATION_BLOCK)
                    ->withPivot('followable_type', 'relation', 'created_at')
                    ->addSelect("{$targetTable}.*", DB::raw("(CASE WHEN {$tablePrefixedForeignKey} IS NOT NULL THEN 1 ELSE 0 END) as {$eachOtherKey}"))
                    ->leftJoin("{$table} as pivot_followables", function ($join) use ($table, $class, $foreignKey) {
                        $join->on('pivot_followables.followable_type', '=', DB::raw(\addcslashes("'{$class}'", '\\')))
                            ->on('pivot_followables.followable_id', '=', "{$table}.{$foreignKey}")
                            ->on("pivot_followables.{$foreignKey}", '=', "{$table}.followable_id");
                    });
    }
}
