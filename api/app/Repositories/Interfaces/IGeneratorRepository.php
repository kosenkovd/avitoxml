<?php

namespace App\Repositories\Interfaces;

use App\Models\Generator;

interface IGeneratorRepository
{
    /**
     * Persist new generator in database.
     *
     * @param string $generatorGuid get generator.
     * @return Generator|null new table id.
     */
    public function get(string $generatorGuid) : ?Generator;
    
    /**
     * Persist new generator in database.
     *
     * @param Generator $generator generator data to insert.
     * @return int new table id.
     */
    public function insert(Generator $generator) : int;

    /**
     * Retrieve last saved generated XML.
     *
     * @param int $generatorId generator id.
     * @return string generated XML, if it exists.
     */
    public function getLastGeneration(int $generatorId) : ?string;

    /**
     * Save new XML generation.
     *
     * @param int $generatorId generator id.
     * @param string $content new generation content.
     */
    public function setLastGeneration(int $generatorId, string $content) : void;

    /**
     * Update model in database.
     *
     * @param Generator $generator generator resource to update
     */
    public function update(Generator $generator) : void;
}
