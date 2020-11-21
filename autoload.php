<?php

spl_autoload_register(function($class) {
    //class directories
    $directories = array(
        '/helpers/',
        '/models/',
        '/services/',
        '/services/classes/'
    );
       
    //for each directory
    foreach($directories as $directory)
    {
        //see if the file exsists
        if(file_exists(__DIR__.$directory.$class . '.class.php'))
        {
            require_once(__DIR__.$directory.$class . '.class.php');
            //only require the class once, so quit after to save effort (if you got more, then name them something else
            return;
        }           
    }
});