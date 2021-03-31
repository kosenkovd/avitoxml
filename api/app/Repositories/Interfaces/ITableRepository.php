<?php

namespace App\Repositories\Interfaces;

use App\Models\Table;
use Exception;

interface ITableRepository
{
    /**
     * Get all tables, or only ones that created by user, if $userId is specified.
     *
     * @param int|null $userId owner user id.
     * @return Table[] found tables.
     */
    public function getTables(?int $userId = null) : array;

    /**
     * Get all tables that does not have generators.
     *
     * @return Table[] found tables.
     */
    public function getGeneratorlessTables(): array;

    /**
     * Persist new table in database.
     *
     * @param Table $table table data to insert.
     * @return int new table id.
     */
    public function insert(Table $table) : int;

    /**
     * Get table by its guid.
     *
     * @param string $tableGuid table guid.
     * @return Table|null table, if found, otherwise null.
     */
    public function get(string $tableGuid) : ?Table;
    
    /**
     * Update table.
     *
     * @param Table $table
     */
    public function update(Table $table) : void;

    /**
     * Update yandex token for table.
     *
     * @param int $tableId
     * @param string $yandexToken
     * @throws Exception
     */
    public function updateYandexToken(int $tableId, string $yandexToken) : void;

	/**
	 * Delete table from database.
	 *
	 * @param Table $table
	 * @return bool
	 */
    public function delete(Table $table) : bool;
}
