<?php


namespace App\Repositories\Interfaces;


use App\Models\TableUpdateLock;

interface ITableUpdateLockRepository
{
    /**
     * Persist new TableUpdateLock in database.
     *
     * @param int $tableId table to insert lock for.
     * @return TableUpdateLock new lock.
     */
    public function insert(int $tableId): TableUpdateLock;

    /**
     * Update model in database.
     *
     * @param TableUpdateLock $lock lock resource to update
     */
    public function update(TableUpdateLock $lock): void;

    /**
     * Get table by table id.
     *
     * @param int $tableId table id.
     * @return TableUpdateLock|null lock, if found, otherwise null.
     */
    public function getByTableId(int $tableId) : ?TableUpdateLock;
}
