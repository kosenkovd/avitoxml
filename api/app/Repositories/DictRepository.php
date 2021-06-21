<?php
    
    
    namespace App\Repositories;
    
    
    use App\Models\Dict\UlaCategory;
    use App\Repositories\Interfaces\IDictRepository;

    class DictRepository extends RepositoryBase implements IDictRepository
    {
        /**
         * @inheritDoc
         */
        public function getUlaCategories(): array
        {
            $mysqli = $this->connect();
    
            $res = $mysqli->query("
            SELECT `id`, `name`
            fROM ".$this->config->getTableUlaName());
    
            if(!$res || !$res->data_seek(0))
            {
                return [];
            }
    
            $categories = [];
            while($row = $res->fetch_assoc())
            {
                $categories[] = new UlaCategory(
                    $row["id"],
                    $row["name"]
                );
            }
    
            $mysqli->close();
    
            return array_values($categories);
        }
    }
