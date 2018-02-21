<?php
    //this script takes a zip file and packages it as a php file, for easy installation.
    //this scrip replaces itself with the installer it creates
    
    error_reporting(-1);
    
    
    
    $myfile = fopen("index.zip", "r") or die("Unable to open file!");
    $text = fread($myfile,filesize("index.zip"));
    fclose($myfile);
    
    $text = base64_encode($text);
    
    
    
    //this string will be writen into the new index.php file
    //1. the read index.zip file is writen as base64 encoded string into a variable
    //2. the scrips decodes the string into a raw file string again
    //3. file string is written to the file installer.zip
    //4. installer.zip is extracted
    //5. installer.zip deleted
    //6. set the header so that the client reloads the site
    $installerString = '<'.'?php'."\r\n".'$zippedBase64='."'".$text."';"."\r\n".'$text = base64_decode($zippedBase64);'."\r\n".'file_put_contents("installer.zip", $text);'."\r\n".'$zip = new ZipArchive;'."\r\n".'$res = $zip->open("installer.zip");'."\r\n".'$zip->extractTo(".");'."\r\n".'$zip->close();'."\r\n".'unlink("./installer.zip");'."\r\n".'header("Refresh:0; url=/");'."\r\n".'?'.'>';
    
    
    file_put_contents('index.php', $installerString);
    unlink('./index.zip');
    
    
?>