<?php


namespace App\Services;

use App\Services\Interfaces\ISpintaxService;

/**
 * Class SpintaxService
 *
 * Algorithm searches for substrings in curly and square braces, then does the following:
 * * for substring in square braces, returns shuffled content, delimiter '|'
 * * for substring in curly braces, returns random element, delimiter '|'
 *
 * Algorithm supports nesting.
 *
 * @package App\Services
 */
class SpintaxService implements ISpintaxService
{
    /**
     * Replaces square braces with one random element in it.
     *
     * @param string[] $matches pattern matches
     * @return string randomized string
     */
    private function replaceInCurlyBraces(array $matches) : string
    {
        $text = $this->processCurlyBraces($matches[1]);
        $parts = explode('|', $text);

        return $parts[array_rand($parts, 1)];
    }

    /**
     * Shuffles text parts in square braces.
     *
     * @param string[] $matches pattern matches
     * @return string randomized string
     */
    private function shuffleSquareBraces(array $matches) : string
    {
        $text = $this->processSquareBraces($matches[1]);
        $separator = '';
        if (preg_match('/^\+(.+)\+\s*(.*)/', $text, $matches)) {
            $text = $matches[2];
            $separator = ($matches[1] === '\n') ? PHP_EOL : $matches[1];
        }
        $parts = $this->getSquareBracesVariants($text);
        shuffle($parts);

        return join($separator, $parts);
    }

    /**
     * Splits string in square braces into available variants.
     *
     * @param string $text content square braces.
     * @return string[] variants.
     */
    private function getSquareBracesVariants(string $text) : array
    {
        $result = [];
        $index = 0;
        $squareNum = 0;

        $charArray = str_split($text);
        foreach($charArray as $char)
        {
            switch($char)
            {
                case "|":
                    if($squareNum === 0)
                    {
                        $index++;
                    }
                    break;
                case "{":
                    $squareNum++;
                    break;
                case "}":
                    $squareNum--;
                    break;
            }

            // non-nested '|' art not parts of variant substrings
            if($char == "|" && $squareNum === 0)
            {
                continue;
            }
            if(!isset($result[$index]))
            {
                $result[$index] = $char;
            }
            else
            {
                $result[$index] .= $char;
            }
        }

        return $result;
    }

    /**
     * Randomizes square brackets.
     *
     * @param string $text text to randomise.
     * @return string randomized result.
     */
    private function processSquareBraces(string $text) : string
    {
        return preg_replace_callback(
            '/\[(((?>[^\[\]]+)|(?R))*?)\]/x',
            array($this, 'shuffleSquareBraces'),
            $text
        );
    }

    /**
     * Randomizes curly brackets.
     *
     * @param string $text text to randomise.
     * @return string randomized result.
     */
    private function processCurlyBraces(string $text) : string
    {
        return preg_replace_callback(
            '/\{(((?>[^\{\}]+)|(?R))*?)\}/x',
            array($this, 'replaceInCurlyBraces'),
            $text
        );
    }

    /**
     * Generates random spintax string.
     *
     * @param string $text input spintax template.
     * @return string randomized text.
     */
    public function randomize(string $text) : string
    {
        // Adding square braces to be able to shuffle elements for input like "cat | dog"
        $text = "[".$text."]";
        
        $text = $this->processSquareBraces($text);

        return $this->processCurlyBraces($text);
    }
}
