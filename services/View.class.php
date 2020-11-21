<?php
// Класс, генерирующий возвращаемые HTML страницы
class View
{
    public static $TPL_MISSING_TABLE_ID = <<<ASD
            <h1>Пожалуйста, укажите идентификатор таблицы</h1>
ASD;

    public static $TPL_INCORRECT_TABLE_ID = <<<ASD
            <h1>Указан несуществующий идентификатор таблицы</h1>
ASD;

    public static function GenerateResponseBody(string $content, string $title = null) : string
    {
        return self::GetPageHeader($title).$content.self::GetPageFooter();
    }
    
    public static function GetIndexPageContent() : string
    {
        return '
    		<a href="/createTable.php?hash='.Constants::getCreateTableHash().'" class="btn btn-primary btn-ghost btn-main">Создать новую таблицу</a>
    		<a href="/listTables.php?hash='.Constants::getTableListHash().'"  class="btn btn-primary btn-ghost btn-main">Просмотреть созданные таблицы</a>
';
    }
    
    public static function GetPageHeader(string $title = null) : string
    {
        if($title == null)
        {
            $title = "&title&";
        }
        
        return '
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
	<title>'.$title.'</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div id="page-container">
    <header class="container-fluid bg-dark" id="header">
    <nav class="container navbar navbar-dark">
      <a class="navbar-brand" href="/index.php?hash='.Constants::getIndexHash().'">Домой</a>
    </nav>
    </header>
    <section class="container" id="main">
        <div class="page-center">
';
    }
    
    public static function GetPageFooter() : string
    {
        return '
        </div>
    </section>
    <footer class="container-fluid bg-dark">
    <nav class="container navbar navbar-dark">
        <small>&#8471; Depech tech</small>
    </nav>
    </footer>
</div>
</body>
</html>';
    }
    
    public static function GenerateTable($rowsContent) : string
    {
        $result = '
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Таблица</th>
                        <th scope="col">Папка</th>
                        <th scope="col">Генератор</th>
                    </tr>
                </thead>
                <tbody>';
        
        $i = 1;
        foreach($rowsContent as $row)
        {
            $result .= '
                    <tr>
                        <th scope="row">'.$i++.'</th>
                        <td>
                            <a href="'.LinkHelper::getGoogleSpreadsheetLink($row["tableID"]).'" target="_blank">'.$row["tableID"].'</a>
                        </td>
                        <td>
                            <a href="'.LinkHelper::getGoogleDriveFolderLink($row["folderID"]).'" target="_blank">'.$row["folderID"].'</a>
                        </td>
                        <td>
                            <a href="'.LinkHelper::getXmlGeneratorLink($row["generatorID"]).'" target="_blank">'.$row["generatorID"].'</a>
                        </td>
                    </tr>
';
        }
        
        $result .= '
                </tbody>
            </table>
';
        
        return $result;
    }
    
    public static function GetCreatedResult($newData) : string
    {
        if($newData == null)
        {
            $newData["tableLink"] = "&tableLink&";
            $newData["folderLink"] = "&googleDriveLink&";
            $newData["generatorLink"] = "&generatorLink&";
        }
        
        return '
            <h1>Таблица успешно создана!</h1>
            <div class="container result">
                <a href="'.$newData["tableLink"].'" target="_blank" class="btn btn-primary btn-lg" role="button" aria-pressed="true">Ссылка на таблицу</a>
                <a href="'.$newData["folderLink"].'" target="_blank" class="btn btn-primary btn-lg" role="button" aria-pressed="true">Ссылка на папку в гугл диске</a>
                <a href="'.$newData["generatorLink"].'" target="_blank" class="btn btn-primary btn-lg" role="button" aria-pressed="true">Ссылка на генератор XML документа</a>
            </div>
';
    }
}

?>