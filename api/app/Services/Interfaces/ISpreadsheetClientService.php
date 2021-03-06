<?php


namespace App\Services\Interfaces;

use DateTime;

interface ISpreadsheetClientService
{
    /**
     * Creates new GoogleSheet from template.
     *
     * @return string new GoogleSheet id.
     */
    public function copyTable(): string;

    /**
     * Get last modified time for file.
     *
     * @param string $fileId file id.
     * @param string $quotaUser user for quota, should be less than 40 symbols.
     * @return DateTime|null last modified time if file found.
     */
    public function getFileModifiedTime(string $fileId, string $quotaUser) : ?DateTime;

    /**
     * Get cells range for GoogleSheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $range range to get.
     * @param string $quotaUser user for quota, should be less than 40 symbols.
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     * @return array cells in chosen range.
     */
    public function getSpreadsheetCellsRange(string $spreadsheetId, string $range, string $quotaUser, bool $toRetry = true) : array;

    /**
     * Update cells range for GoogleSheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $range range to update.
     * @param array $values values to update.
     * @param array $params params of request to update.
     * @param string $quotaUser user for quota, should be less than 40 symbols.
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     * @return void
     */
    public function updateSpreadsheetCellsRange(
        string $spreadsheetId,
        string $range,
        array $values,
        array $params,
        string $quotaUser,
        bool $toRetry = true
    ) : void;

    /**
     * Updates GoogleSheet cell content.
     *
     * @param string $tableID table id.
     * @param string $targetSheet sheet name.
     * @param string $cell cell name.
     * @param string $content content to put in cell.
     * @param string $quotaUser user for quota, should be less than 40 symbols.
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     * @return void
     */
    function updateCellContent(
        string $tableID, string $targetSheet, string $cell, string $content, string $quotaUser, bool $toRetry = true): void;

    /**
     * Get all sheets that are present in spreadsheet.
     *
     * @param string $tableId table id.
     * @param string $quotaUser user for quota, should be less than 40 symbols.
     * @return array list of sheet names.
     */
    public function getSheets(string $tableId, string $quotaUser): array;
}
