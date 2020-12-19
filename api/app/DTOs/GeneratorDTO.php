<?php


namespace App\DTOs;


class GeneratorDTO
{
    public string $targetPlatform;

    public string $generatorUrl;

    /**
     * GeneratorDTO constructor.
     * @param string $targetPlatform
     * @param string $generatorUrl
     */
    public function __construct(string $targetPlatform, string $generatorUrl)
    {
        $this->targetPlatform = $targetPlatform;
        $this->generatorUrl = $generatorUrl;
    }
}
