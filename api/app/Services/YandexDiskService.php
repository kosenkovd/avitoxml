<?php
    
    
    namespace App\Services;
    
    use App\Configuration\Config;
    use App\Services\Interfaces\IYandexDiskService;
    use Exception;
    use \Leonied7\Yandex\Disk;
    use \Leonied7\Yandex\Disk\Item\File;

    /**
     * Handles communication with Yandex Drive services.
     * @package App\Services
     */
    class YandexDiskService implements IYandexDiskService {
        private Config $config;
        private Disk $disk;
        private string $baseFolder;
        
//        /**
//         * Sets default permissions to Yandex object.
//         *
//         * @param $id string Yandex resource id.
//         */
//        private function setPermissions(string $id): void
//        {
//        }
    
        /**
         * YandexDiskService constructor.
         */
        public function __construct()
        {
            $this->config = new Config();
        }
    
        /**
         * @inheritDoc
         * @param string $baseFolder
         * @param string $token
         * @throws Disk\Exception\InvalidArgumentException
         */
        public function init(string $baseFolder, string $token): void
        {
            $this->baseFolder = $baseFolder;
            $this->disk = new Disk($token);
        }
    
        /**
         * @inheritDoc
         * @throws Exception
         */
        public function createFolder(
            string $name = null,
            string $parentId = null,
            bool $setPermissions = true,
            bool $toRetry = true): ?string
        {
            if(is_null($name))
            {
                $name = strval(time());
            }
            if(is_null($parentId))
            {
                $folderPath = '/'.$name.'/';
            } else {
                $folderPath = '/'.$parentId.'/'.$name.'/';
            }
    
            $diskFolder = $this->disk->directory($folderPath);
           
            try
            {
                $result = $diskFolder->create();
            }
            catch (Exception $exception)
            {
                if(!$toRetry)
                {
                    throw $exception;
                }
            
                sleep(60);
                $result = $diskFolder->create();
            }
        
            if ($result) {
                return $folderPath;
            } else {
                throw new Exception('Can\'t Create disk folder');
            }
        }
    
        /**
         * @inheritDoc
         */
        public function getChildFolderByName(string $folderID, string $subFolderName, bool $toRetry = true): ?string
        {
            return '/'.$this->baseFolder.'/'.$subFolderName.'/';
        }
    
        /**
         * @inheritDoc
         * @throws Exception
         */
        public function listFolderImages(string $folderID, bool $toRetry = true): array
        {
//            $folderPath = '/'.$this->baseFolder.'/'.$folderID.'/'; сюда приходит сразу полный путь к папке
            $folderPath = $folderID;
            try
            {
                $directory = $this->disk->directory($folderPath);
                $arChild = $directory->getChildren();
                
                return array_filter(array_map(function ($child) {
                    if($child->isFile()) {
                        /** @var Disk\Item\File $file */
                        $file = $child;
                        return $file;
                    } else {
                        return null;
                    }
                }, $arChild));
            }
            catch (Exception $exception)
            {
                if(!$toRetry)
                {
                    throw $exception;
                }
            
                sleep(60);
                // retry
                $directory = $this->disk->directory('/'.$this->baseFolder.'/'.$folderID.'/');
                $arChild = $directory->getChildren();
    
                
                return array_filter(array_map(function ($child) {
                    if($child->isFile()) {
                        /** @var Disk\Item\File $file */
                        $file = $child;
                        return $file;
                    } else {
                        return null;
                    }
                }, $arChild));
            }
        }
    
        /**
         * @inheritDoc
         * @throws Exception
         */
        public function moveFile(
            File $file,
            string $folderID,
            string $newName = null,
            bool $toRetry = true): void
        {
            $folderPath = '/'.$this->baseFolder.'/'.$folderID.'/';
    
            if(!is_null($newName))
            {
                $newFilePath = $folderPath.$newName;
            } else {
                $newFilePath = $folderPath;
            }
            
            try
            {
                $file->move($newFilePath);
            }
            catch (Exception $exception)
            {
                if(!$toRetry)
                {
                    throw $exception;
                }
            
                sleep(60);
                $file->move($newFilePath);
            }
        }
    }
