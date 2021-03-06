<?php


    namespace App\Services;

    use App\Configuration\Config;
    use App\Services\Interfaces\IYandexDiskService;
    use Exception;
    use \Arhitector\Yandex\Disk;
    use Illuminate\Support\Facades\Log;

    /**
     * Handles communication with Yandex Drive services.
     * @package App\Services
     */
    class YandexDiskService implements IYandexDiskService{
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

            $result = $this->disk->getResource($folderPath)->create();

            var_dump($result->toArray());
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
                $directory = $this->disk->getResource($folderPath);

                $imageNames = [];
                foreach ($directory->items as $item)
                {
                    $imageNames[] = $item->get('path');
                }
                return $imageNames;
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

            $folder = $this->disk->getResource($this->cleanupPath("/".$folderID));
            if(!$folder->has())
            {
                $folder->create();
            }

            $file = $this->disk->getResource($this->cleanupPath($currentPath));
            echo "Save to ".$newFilePath." from ".$currentPath.PHP_EOL;
            $result = $file->move($this->cleanupPath($newFilePath));
            if(!$result)
            {
                var_dump($result->getStatus());
                echo "Move error!!!!!!".PHP_EOL;
            }
        }

        /**
         * @inheritDoc
         */
        public function exists(string $folderID): bool
        {
            $folderPath = "/".$folderID;

            return $this->disk->getResource($folderPath)->has();
        }

        /**
         * @inheritDoc
         */
        public function getFileUrl(string $filePath) : ?string
        {
            $file = $this->disk->getResource($filePath);
            if(!$file->has())
            {
                return null;
            }

            return $file->get('file');
        }
    }
