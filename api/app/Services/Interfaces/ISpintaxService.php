<?php


namespace App\Services\Interfaces;

/**
 * Interface ISpintaxService
 *
 * Algorithm searches for substrings in curly and square braces, then does the following:
 * * for substring in square braces, returns shuffled content, delimiter '|'
 * * for substring in curly braces, returns random element, delimiter '|'
 *
 * Algorithm supports nesting.
 *
 * @package App\Services\Interfaces
 */
interface ISpintaxService
{
    /**
     * Generates random spintax string.
     *
     * @param string $text input spintax template.
     * @return string randomized text.
     */
    public function randomize(string $text) : string;
}
