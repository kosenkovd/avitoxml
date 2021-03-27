<?php


namespace App\Helpers;

/**
 * Class SpreadsheetHelper
 *
 * Contains helper methods for spreadsheets.
 * @package App\Helpers
 */
class SpreadsheetHelper
{
    /**
     * Gets spreadsheet column letter by column number.
     *
     * @param int $numCol column number.
     * @return string column letter.
     */
    public static function getColumnLetterByNumber(int $numCol): string
    {
        // В английском алфавите 26 букв
        $remainder = $numCol % 26;
        $quotient = ($numCol - $remainder) / 26;
        if($quotient > 0) {
            return self::getColumnLetterByNumber($quotient - 1).chr($remainder + 65);
        } else {
            // 65 - это код символа A
            return chr($remainder + 65);
        }
    }
}
