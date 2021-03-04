<?php
    
    
    namespace App\Services;
    
    use App\Configuration\Config;
    use App\Services\Interfaces\IYandexDiskService;
    use Exception;
    use \Leonied7\Yandex\Disk;
    use Illuminate\Support\Facades\Log;
    
    /**
     * Handles communication with Yandex Drive services.
     * @package App\Services
     */
    class YandexDiskService implements IYandexDiskService {
        private Config $config;
        private Disk $disk;
        
        private function cleanupPath(string $path) : string
        {
            return preg_replace('/\/+/', '/', $path);
        }
        
        /**
         * YandexDiskService constructor.
         */
        public function __construct()
        {
            $this->config = new Config();
        }
        
        /**
         * @inheritDoc
         */
        public function init(string $token): void
        {
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
                $folderPath = '/'.$name;
            } else {
                $folderPath = '/'.$parentId.'/'.$name;
            }
            
            $result = $this->disk->directory($folderPath)->create();
            
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
            return '/'.$subFolderName.'/';
        }
        
        /**
         * @inheritDoc
         * @throws Exception
         */
        public function listFolderImages(string $folderID, bool $toRetry = true): array
        {
            $folderPath = $this->cleanupPath("/".$folderID);
            
            try
            {
                $directory = $this->disk->directory($folderPath);
                return $directory->getChildren();
            }
            catch (Exception $exc)
            {
                Log::error("Error during image list: (folderId: ".$folderPath.", exception: ".$exc->getMessage().")");
                echo "Error during image list: (folderId: ".$folderPath.", exception: ".$exc->getMessage().")".PHP_EOL;
                return [];
            }
        }
        
        /**
         * @inheritDoc
         * @throws Exception
         */
        public function moveFile(
            string $currentPath,
            string $folderID,
            string $newName = null,
            bool $toRetry = true): void
        {
            $folderPath = '/'.$folderID.'/';
            
            if(!is_null($newName))
            {
                $newFilePath = $folderPath.$newName;
            }
            else
            {
                $filePathArray = explode('/', $currentPath);
                $fileName = $filePathArray[count($filePathArray) - 1];
                $newFilePath = $folderPath.$fileName;
            }
            $newFilePath = preg_replace('/\s/', '', $newFilePath);
            
            $folder = $this->disk->directory($this->cleanupPath("/".$folderID));
            if(!$folder->has())
            {
                $folder->create();
            }
            
            $file = $this->disk->file($this->cleanupPath($currentPath));
            echo "Save to ".$newFilePath." from ".$currentPath.PHP_EOL;
            $result = $file->move($this->cleanupPath($newFilePath));
            if(!$result)
            {
                echo "Move error!!!!!!".PHP_EOL;
            }
        }
        
        /**
         * @inheritDoc
         * @throws Disk\Exception\InvalidArgumentException
         */
        public function exists(string $folderID): bool
        {
            $folderPath = "/".$folderID;
            
            return $this->disk->directory($folderPath)->has();
        }
        
        /**
         * @inheritDoc
         * @throws Disk\Exception\InvalidArgumentException
         */
        public function getFileUrl(string $filePath) : ?string
        {
            $file = $this->disk->file($filePath);
            if(!$file->has())
            {
                return null;
            }
            
            $file->download();
            
            $result = Disk\Collection\ResultList::getInstance()->getLast();
            return $result->getActualResult();
        }
    }
