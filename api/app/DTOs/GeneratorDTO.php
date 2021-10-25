<?php


namespace App\DTOs;


class GeneratorDTO
{
    public string $targetPlatform;

    public string $generatorUrl;
    
    public string $generatorGuid;
    
    public int $maxAds;
    
    public bool $subscribed;

    public ?int $subscribedMaxAds;

    /**
     * @param int $subscribedMaxAds
     *
     * @return GeneratorDTO
     */
    public function setSubscribedMaxAds(?int $subscribedMaxAds): GeneratorDTO
    {
        $this->subscribedMaxAds = $subscribedMaxAds;
        return $this;
    }
    
    /**
     * @param bool $subscribed
     *
     * @return GeneratorDTO
     */
    public function setSubscribed(bool $subscribed): GeneratorDTO
    {
        $this->subscribed = $subscribed;
        return $this;
    }
    
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
