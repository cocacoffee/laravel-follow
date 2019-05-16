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
 * Trait CanBeHidden.
 */
trait CanBeHidden
{
    /**
     * Check if user is hid by given user.
     *
     * @param int $user
     *
     * @return bool
     */
    public function isHiddenBy($user)
    {
        return Follow::isRelationExists($this, 'hiders', $user);
    }

    /**
     * Return followers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function hiders()
    {
        $table = config('follow.followable_table');
        $class = \get_class($this);
        $userTable = config('follow.users_table_name', 'users');
        $foreignKey = config('follow.users_table_foreign_key', 'user_id');
        $tablePrefixedForeignKey = app('db.connection')->getQueryGrammar()->wrap(\sprintf('pivot_followables.%s', $foreignKey));
        $eachOtherKey = app('db.connection')->getQueryGrammar()->wrap('pivot_each_other');

        return $this->morphToMany(config('follow.user_model'), config('follow.morph_prefix'), config('follow.followable_table'))
            ->wherePivot('relation', '=', Follow::RELATION_HIDE)
            ->withPivot('followable_type', 'relation', 'created_at')
            ->addSelect("{$userTable}.*", DB::raw("(CASE WHEN {$tablePrefixedForeignKey} IS NOT NULL THEN 1 ELSE 0 END) as {$eachOtherKey}"))
            ->leftJoin("{$table} as pivot_followables", function ($join) use ($table, $class, $foreignKey) {
                $join->on('pivot_followables.followable_type', '=', DB::raw(\addcslashes("'{$class}'", '\\')))
                    ->on('pivot_followables.followable_id', '=', "{$table}.{$foreignKey}")
                    ->on("pivot_followables.{$foreignKey}", '=', "{$table}.followable_id");
            });
    }
}
