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

        /**
         * YandexDiskService constructor.
         */
        public function __construct()
        {
            $this->config = new Config();
        }

        /**
         * @inheritDoc
         * @throws Disk\Exception\InvalidArgumentException
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

            $diskFolder = $this->disk->directory($folderPath);

            $result = $diskFolder->create();

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
            $folderPath = "/".$folderID;

            $directory = $this->disk->directory($folderPath);

            return $directory->getChildren();
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
            $folderPath = '/'.$folderID.'/';

            if(!is_null($newName))
            {
                $newFilePath = $folderPath.$newName;
            }
            else
            {
                $filePathArray = explode('/', $file->getPath());
                $fileName = $filePathArray[count($filePathArray) - 1];
                $newFilePath = $folderPath.$fileName;
            }
            $newFilePath = preg_replace('/\s/', '', $newFilePath);
            echo "Save to ".$newFilePath.PHP_EOL;

            if(!$file->move($newFilePath))
            {
                echo "Move error!!!!!!".PHP_EOL;
                var_dump(Disk\Collection\ResultList::getInstance()->getLast());
            }
        }

        /**
         * @inheritDoc
         */
        public function downloadFile(string $fileID)
        {
            $file = $this->disk->file($fileID);
            $file->download(); //bool
            // получение последнего результата запроса
            $result = Disk\Collection\ResultList::getInstance()->getLast();
            return $result->getActualResult();
        }
    }
