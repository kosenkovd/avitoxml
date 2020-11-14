<?php
    require_once(__DIR__."/../autoload.php");
    require_once(__DIR__ . "/../functions.php");
    require_once('../../../vendor/autoload.php');
    $client = new Google_Client();
    $client->setApplicationName('Google Sheets Depeche');
    $client->setAccessType('offline');
    $client->setAuthConfig('../services/config/GoogleAccountConfig.json');
    $client->addScope(Google_Service_Drive::DRIVE);
    $service = new Google_Service_Drive($client);
    
    $fileID = $_GET['fileID'];
    
    $response = $service->files->get($fileID, ['alt' => 'media']);
    $result = $response->getBody()->getContents();
    
    $contentType = $response->getHeader("Content-Type")[0];

    header('Content-Type: '.$contentType);
    echo $result;

