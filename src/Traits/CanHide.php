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
 * Trait CanHide.
 */
trait CanHide
{
    /**
     * Hide an item or items.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                        $class
     *
     * @throws \Exception
     *
     * @return array
     */
    public function hide($targets, $class = __CLASS__)
    {
        return Follow::attachRelations($this, 'hides', $targets, $class);
    }

    /**
     * UnHide an item or items.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                        $class
     *
     * @return array
     */
    public function unHide($targets, $class = __CLASS__)
    {
        return Follow::detachRelations($this, 'hides', $targets, $class);
    }

    /**
     * Toggle hide an item or items.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                        $class
     *
     * @throws \Exception
     *
     * @return array
     */
    public function toggleHide($targets, $class = __CLASS__)
    {
        return Follow::toggleRelations($this, 'hides', $targets, $class);
    }

    /**
     * Check if user is hiding given item.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $target
     * @param string                                        $class
     *
     * @return bool
     */
    public function isHiding($target, $class = __CLASS__)
    {
        return Follow::isRelationExists($this, 'hides', $target, $class);
    }

    /**
     * Check if user and target user is hiding each other.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $target
     * @param string                                        $class
     *
     * @return bool
     */
    public function areHidingEachOther($target, $class = __CLASS__)
    {
        return Follow::isRelationExists($this, 'hides', $target, $class) && Follow::isRelationExists($target, 'hides', $this, $class);
    }

    /**
     * Return item hidings.
     *
     * @param string $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function hides($class = __CLASS__)
    {
        $table = config('follow.followable_table');
        $foreignKey = config('follow.users_table_foreign_key', 'user_id');
        $targetTable = (new $class())->getTable();
        $tablePrefixedForeignKey = app('db.connection')->getQueryGrammar()->wrap(\sprintf('pivot_followables.%s', $foreignKey));
        $eachOtherKey = app('db.connection')->getQueryGrammar()->wrap('pivot_each_other');

        return $this->morphedByMany($class, config('follow.morph_prefix'), $table)
                    ->wherePivot('relation', '=', Follow::RELATION_HIDE)
                    ->withPivot('followable_type', 'relation', 'created_at')
                    ->addSelect("{$targetTable}.*", DB::raw("(CASE WHEN {$tablePrefixedForeignKey} IS NOT NULL THEN 1 ELSE 0 END) as {$eachOtherKey}"))
                    ->leftJoin("{$table} as pivot_followables", function ($join) use ($table, $class, $foreignKey) {
                        $join->on('pivot_followables.followable_type', '=', DB::raw(\addcslashes("'{$class}'", '\\')))
                            ->on('pivot_followables.followable_id', '=', "{$table}.{$foreignKey}")
                            ->on("pivot_followables.{$foreignKey}", '=', "{$table}.followable_id");
                    });
    }
}
