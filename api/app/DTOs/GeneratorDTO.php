<?php


namespace App\DTOs;


class GeneratorDTO
{
    public string $targetPlatform;

    public string $generatorUrl;
    
    public string $generatorGuid;
    
    public int $maxAds;
    
    /**
     * @param string $targetPlatform
     * @return GeneratorDTO
     */
    public function setTargetPlatform(string $targetPlatform): GeneratorDTO
    {
        $this->targetPlatform = $targetPlatform;
        return $this;
    }
    
    /**
     * @param string $generatorUrl
     * @return GeneratorDTO
     */
    public function setGeneratorUrl(string $generatorUrl): GeneratorDTO
    {
        $this->generatorUrl = $generatorUrl;
        return $this;
    }
    
    /**
     * @param string $generatorGuid
     * @return GeneratorDTO
     */
    public function setGeneratorGuid(string $generatorGuid): GeneratorDTO
    {
        $this->generatorGuid = $generatorGuid;
        return $this;
    }
    
    /**
     * @param int $maxAds
     * @return GeneratorDTO
     */
    public function setMaxAds(int $maxAds): GeneratorDTO
    {
        $this->maxAds = $maxAds;
        return $this;
    }
    
}
