<?php
    $client = new Google_Client();
    $client->setApplicationName('Google Sheets Depeche');
    $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
    $client->setAccessType('offline');
    $client->setAuthConfig($_SERVER["DOCUMENT_ROOT"] . '/services/config/GoogleAccountConfig.json');
    $service = new Google_Service_Sheets($client);
    $copySpreadsheetId = '1iZiPNNjReXtxF65ZMmodkPmuLvR-DVAv7Uow_4QsZOM';
    
    $drivePermissions = new Google_Service_Drive_Permission();
    $drivePermissions->setRole('writer');
    $drivePermissions->setType('anyone');
    
    $folderId = '1DmfncP64A8P7fV8K3Suj81uVKwlFebTU';
    
    
