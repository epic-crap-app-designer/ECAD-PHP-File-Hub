<?php
    //change in the folowing only in the config.php file!!!
    $debug = false;
    $secret_word = "word";
    $ecad_php_version ="ECAD PHP fileviewer v0.1.19b";
    $ecad_php_version_number = "v0.1.19";
    installifneeded($secret_word, $ecad_php_version_number);
    $show_ecad_php_version_on_title = true;
    $maximalUploadSize = "70M"; //if changed needs also to be set in the .htaccess file!! (php_value upload_max_filesize 50M and php_value post_max_size 50M)
    $showAdministratorPath = false;
    $userIsAdmin = false;
    $nichtgelisteteDatein = array("index.php", ".htaccess", ".", "..");
    $showFileViewer = false;
    
    //variables for compatiblety
    $canAccessSystemFolder = false;
    $log_fileUpload = true;

    //load config
    include "config.php";
    
    //1-ignores errors 2-shows errors
    error_reporting(1);
    
    //authentification service cockie
    $authentificated = false;
    
    //check if login cockie exists on host
    if ($_COOKIE['ECAD_PHP_fileviewer_login']) {
        list($c_username,$cookie_hash) = split(',',$_COOKIE['ECAD_PHP_fileviewer_login']);
        //verify if user exists
        if(file_exists($datarootpath."/users/".$c_username)){
            //load user information
            include $datarootpath."/users/".$c_username."/userconfig.php";
            //load saved session cockies
            include $datarootpath."/users/".$c_username.'/login.php';
            //check if cockie is valid
            if (strstr($acceptableuserLoginCockies, "-".$_COOKIE['ECAD_PHP_fileviewer_login']."-")){
                $user = $c_username;
                $userpath="/".$user;
                
                $authentificated = true;
            } else {
                $authentificated = false;
                ecad_php_log($datarootpath,"WARNING","no valid cockie");
            }
        }
        //remove session cockie from server
        if($_GET["action"] == "logout"){
            removeLoginCockieFromServer($datarootpath, $c_username);
            $authentificated = false;
        }
    }else{
        $authentificated = false;
    }
    //remove login cockie when user is logging out from client
    if($_GET["action"] == "logout"){
        ecad_php_log($datarootpath,"INFO","logout");
        //sets client cockie null
        setcookie('ECAD_PHP_fileviewer_login',"null");
        $authentificated = false;
        header("Refresh:0; url=index.php");
    }

    //-------------------
if ($authentificated) {
    if($user == "admin"){
        $userIsAdmin = true;
    }
    if($userIsAdmin){//administrator is logged in
        
        //path validate end-----
        
        $show_user_interface = true;
        //download log file
        if ( $_GET["action"] == "getLogFile" ) {
            $show_user_interface = false;
            downloadLogFile($datarootpath);
        }
        //prints administrator interface header
        if($show_user_interface && $_GET["path"] == ""){
            printsAdministratorInterfaceHeader($ecad_php_version, $user);
        }
        //administrative functions getter---------------------------------
        //shows a confirmation window if the user should really be deleted
        if ( isset( $_POST['delete_user'] ) ) {
            $show_user_interface = false;
            echo'</br><form method="POST" action="">Really delete user: '.$_POST['user_to_delete'].'?<span style="padding-left:80px"></span><input type="hidden" name="user_to_delete" value="'.$_POST['user_to_delete'].'"><input name="really_delete_user" value="delete" type="submit"><input name="" value="abort" type="submit"></form>';
        }
        //deletes the given user
        if ( isset( $_POST['really_delete_user'] ) ) {
            rrmdir($datarootpath."/users/".$_POST['user_to_delete']);
        }
        //shows the create user view
        if ( isset( $_POST['create_user'] ) ) {
            $show_user_interface = false;
            printCreateUserView();
        }
        //shows the edit user view
        if ( isset( $_POST['edit_user'] ) ) {
            $show_user_interface = false;
            printEditUserView($datarootpath);
        }
        //creates a new user
        if ( isset( $_POST['create_user_submit'] ) ) {
            if($_POST['username'] != ""){
                create_user($_POST['username'],$_POST['password'],$datarootpath,$secret_word,(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false"),(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false"));
            }
        }
        //edits a user
        if ( isset( $_POST['edit_user_submit'] ) ) {
            edit_user_submit($datarootpath, $secret_word);
        }
        //closes the session of a given user
        if ( isset( $_POST['logout_user'] ) ) {
            logout_user($datarootpath);
        }
        //interface for administrators
        if($show_user_interface){
            if ($_GET["path"] == "" or $_GET["path"] == ""){
                //user list interface for administrator
                showUserListPannel($datarootpath, $canAccessSystemFolder);
            }else{
                 //file viewer interface for administrator
                $path = $_GET["path"];
                $showFileViewer = true;
                echo '<a href="index.php?path="><--  back to user administration </a></br>';
            }
        }
    }
    
    //Normal user handling-------------------------------------------------------------------------------------------------------------------------------------
    if($userIsAdmin == false or $showFileViewer){
        //normal user logged in or administrator is showing file viewer
        //get the path and full path
        if($canAccessSystemFolder){
            $path = getSafePath($datarootpath, "");
            $fullpath = getSafeFullPath($datarootpath, "", $path);
        }else{
            $path = getSafePath($datarootpath, "/users/".$userpath."/data");
            $fullpath = getSafeFullPath($datarootpath, "/users/".$userpath."/data", $path);
        }
        if(is_file($fullpath)){
            //download file if path is a file
            ecad_php_log($datarootpath,"INFO","file download ".'['.$path.']');
            makeDownload($fullpath, $path);
        }else{
            //normal user -----------------------------------------
            $show_user_interface = true;
            //user inputs ---------------------------------------------------------------------------------------------
            //create new folder
            if ( isset( $_POST['create_Folder'] ) && $can_delete ) {
                $show_user_interface = true;
                create_Folder($fullpath);
            }
            //show rename folder / file form
            if ( isset( $_POST['rename_FolderOrFile'] ) && $can_delete ) {
                $show_user_interface = false;
                rename_FolderOrFile($nichtgelisteteDatein, $fullpath);
            }
            //rename folder or file if user has comfirmed
            if ( isset( $_POST['rename_FolderOrFile_submit'] ) && $can_delete ) {
                $show_user_interface = true;
                rename_FolderOrFile_submit($nichtgelisteteDatein, $fullpath);
            }
            //show form with delete file
            if ( isset( $_POST['delete_FolderOrFile'] ) && $can_delete ) {
                $show_user_interface = false;
                delete_FolderOrFile($nichtgelisteteDatein, $fullpath);
            }
            //deletes files if user has comfirmed deltion
            if ( isset( $_POST['delete_FolderOrFile_submit'] ) && $can_delete ) {
                $show_user_interface = true;
                delete_FolderOrFile_submit($nichtgelisteteDatein, $fullpath);
            }
            //upload file if file is being uploaded
            if ( isset( $_POST['upload_single_file'] ) && $can_upload ) {
                upload_single_file($datarootpath, $log_fileUpload, $fullpath, $path, $maximalUploadSize, $maximalUploadSize);
            }
            //show user interface if nothing else has happend
            if($show_user_interface){
                printUserInterfaceFileViewer($user, $path, $fullpath, $datarootpath, $can_delete, $can_upload, $nichtgelisteteDatein, $ecad_php_version);
            }
            
            //end of system for logged in users------------------------------------------------------------------------
        }
        //end of file view------------------------
    }
}else{
    //no valid cockie found
    //login system--------------------------------------------------
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    
    $loginaccepted = false;
    if(file_exists($datarootpath."/users/".$user)){
        include $datarootpath."/users/".$user."/userconfig.php";
        if(md5($pass.$secret_word) == $userpasswordHash){
            $loginaccepted = true;
        }
    }
    if($loginaccepted && $_POST['user'] != null)
    {
        //handel when login sucessfull
        handelLoginAccepted($datarootpath, $user, $pass, $secret_word);
    }
    else
    {
        //handel if not logged in and no valid login
        handelLoginScreenView($show_ecad_php_version_on_title, $ecad_php_version, $datarootpath);
    }
}
//beginning of the functions --------------------------------------------------------------------------------------------------------------------------------------------------------------------
    ?><?php
        
function curPageURL() {
    $pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

function makeDownloadHead($file, $type, $filename) {
    
    
    header("Content-Type: $type");
    
    //header("Content-Disposition: attachment; filename=\"$file\"");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    //Give client the file size
    header("Content-length: ".filesize($file));

}
?><?php
function installifneeded($secret_word, $ecad_php_version_number) {
        //$secret_word = "word";
    if(!file_exists("config.php")){
        //ecad php config file
        $ecadphpconfigfile = fopen("config.php", "w");
        $ecadphpconfigStandard = '<?php'."\r\n".'$datarootpath='."'".__DIR__.'/ECAD PHP fileviewer X data'."'".';'."\r\n".'$firstInstallationVersion='."'".$ecad_php_version_number."'".';'."\r\n".'$adminPassword="admin";'."\r\n".'?>'.'<?php'."\r\n".'$user='.'"user0";'."\r\n".'$userpath='.'"/user0";'."\r\n".'$log_fileUpload=true;'."\r\n".'?>';
fwrite($ecadphpconfigfile, $ecadphpconfigStandard);
fclose($ecadphpconfigfile);
//ecad php data folder
mkdir('./ECAD PHP fileviewer X data/shares', 0777, true);
mkdir('./ECAD PHP fileviewer X data/pages', 0777, true);
mkdir('./ECAD PHP fileviewer X data/users/user0/data/test', 0777, true);
mkdir('./ECAD PHP fileviewer X data/users/user0/downloadpreperation', 0777, true);

//create user0
$ecad_php_user_config_file = fopen('./ECAD PHP fileviewer X data/users/user0/userconfig.php', "w");
$user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5("admin".$secret_word)."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload= false;'."\r\n".'$can_delete= false;'."\r\n".'?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);
//create user0 password
$ecad_php_user_config_file = fopen('./ECAD PHP fileviewer X data/users/user0/login.php', "w");
$user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);

//create admin
mkdir('./ECAD PHP fileviewer X data/users/admin');
$ecad_php_user_config_file = fopen('./ECAD PHP fileviewer X data/users/admin/userconfig.php', "w");
$user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5("admin".$secret_word)."'".';'."\r\n".'$userIsAdmin= true;'."\r\n".'$can_upload= true;'."\r\n".'$can_delete= true;'."\r\n".'$canAccessSystemFolder= true;'.'?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);
//create admin password
$ecad_php_user_config_file = fopen('./ECAD PHP fileviewer X data/users/admin/login.php', "w");
$user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);

//configurate htaccess
$ecad_php_htaccess_file = fopen('./ECAD PHP fileviewer X data/.htaccess', "w");
$ecad_php_htaccess_file_Standard = '<Directory ./>'."\r\n".'Order deny,Allow'."\r\n".'Deny from all'."\r\n".'</Directory>';
fwrite($ecad_php_htaccess_file, $ecad_php_htaccess_file_Standard);
fclose($ecad_php_htaccess_file);

//config htaccess in root folder
file_put_contents(".htaccess","\r\nphp_value upload_max_filesize 50M\r\nphp_value post_max_size 50M",FILE_APPEND);

ecad_php_log(__DIR__.'/ECAD PHP fileviewer X data',"INFO","ECAD PHP fileviewer successfully installed");
    }

}
?><?php
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir."/".$object))
                    rrmdir($dir."/".$object);
                else
                    unlink($dir."/".$object);
            } 
        }
        rmdir($dir); 
    } 
}
?><?php
    function create_user($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word,$toeditUser_can_upload,$toeditUser_can_delete){
        ecad_php_log($ECAD_PHP_fileviewer_X_data_folder,"INFO","user created ".'['.$toCreateUsername.']');
        //create user folders
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername);
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/data');
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/downloadpreperation');
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5($toCreateUserPassword.$secret_word)."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload='.$toeditUser_can_upload.";\r\n".'$can_delete='.$toeditUser_can_delete.";\r\n".'$canAccessSystemFolder=false;'.'?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);
        //create user cockie file
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/login.php', "w");
        $user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);
                            
    }
?><?php
    function edit_user($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word,$toeditUser_can_upload,$toeditUser_can_delete,$canAccessSystemFolder){
        ecad_php_log($ECAD_PHP_fileviewer_X_data_folder,"INFO","user edited ".'['.$toCreateUsername.']');
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5($toCreateUserPassword.$secret_word)."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload='.$toeditUser_can_upload.";\r\n".'$can_delete='.$toeditUser_can_delete.";\r\n".'$canAccessSystemFolder='.$canAccessSystemFolder.";\r\n".'?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);
        //create user cockie file
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/login.php', "w");
        $user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);

}
?><?php
    function edit_user_keep_password($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word,$toeditUser_can_upload,$toeditUser_can_delete,$canAccessSystemFolder){
        ecad_php_log($ECAD_PHP_fileviewer_X_data_folder,"INFO","user edited (password kept) ".'['.$toCreateUsername.']');
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".$toCreateUserPassword."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload='.$toeditUser_can_upload.";\r\n".'$can_delete='.$toeditUser_can_delete.";\r\n".'$canAccessSystemFolder='.$canAccessSystemFolder.";\r\n".'?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);

}
?><?php
    //ecad_php_log($datarootpath,"TYPE","MESSAGE");
    function ecad_php_log($ECAD_PHP_fileviewer_X_data_folder,$type,$log_message){
        //get IP
        $client_Address = $_SERVER['REMOTE_ADDR'];
        //get usercockie
        if(!isset($_COOKIE['ECAD_PHP_fileviewer_login'])){
            $user_cockie = "none";
        }else{
            $user_cockie = $_COOKIE['ECAD_PHP_fileviewer_login'];
        }
        //get time
        $current_time = date("Y.m.d-H.i.s",time());
        $log_text = '['.$current_time.']'.'['.$type.']'.'['.$client_Address.']'.'[cockie: '.$user_cockie.'] '.$log_message."\r\n";
        file_put_contents ($ECAD_PHP_fileviewer_X_data_folder."/ecadPHPLog.log",$log_text,FILE_APPEND);
    }
?><?php
    //User interface Printer -----------------------------------------------------------------------------------------------------------------------------------
    function printUserInterfaceFileViewer($user, $path, $fullpath, $datarootpath, $can_delete, $can_upload, $nichtgelisteteDatein, $ecad_php_version){
        
        //prints head of user interface
        printUserHeader($user, $ecad_php_version);
        
        //prints new path display system
        printUserPath($path);
        
        if(file_exists($fullpath.'/')){
            //logs the file request
            ecad_php_log($datarootpath,"INFO","folder request ".'['.$path.']');
            
            //get files and sort
            $files = scandir($fullpath.'/');
            sort($files, SORT_NATURAL);
            //counts howmany files are found
            $datein = 0;
            
            //prints form header and buttons
            printFileEditUploadDeleteCreateButtons($can_delete, $can_upload);
            
            //prints upload form
            printUserFileUploadScript();
            
            foreach($files as $file){
                //print files
                //$datein++;
                $datein = $datein + printFileAndInfo($file , $nichtgelisteteDatein, $path, $can_delete, $fullpath);
            }
            echo "\r\n".'</form>';
            echo "</br>";
            if($datein == 1){
                echo $datein." Object";
                //echo "no files";
            }else{
                echo $datein." Objects";
            }
        }else{
            ecad_php_log($datarootpath,"INFO","folder/file not found ".'['.$path.']');
            echo "</br> Folder/File not found";
        }
        echo "</body>";
        echo "</html>";
    }
    function printUserFileUploadScript(){
        ?>
<div id="uploadFormDiv">

Select a file to upload:
<input type="file" name="fileToUpload" id="fileToUpload">
<input type="submit" value="Upload File" name="upload_single_file">
</div>
<script>
document.getElementById("uploadFormDiv").style.visibility = 'hidden';
document.getElementById("uploadFormDiv").style.height = '0px';
var uploadVisible = false;
function showUploadFunction(){
    if(uploadVisible){
        document.getElementById("uploadFormDiv").style.visibility = 'hidden';
        document.getElementById("uploadFormDiv").style.height = '0px';
        uploadVisible = false;
    }else{
        document.getElementById("uploadFormDiv").style.visibility = 'visible';
        document.getElementById("uploadFormDiv").style.height = 'auto';
        uploadVisible = true;;
    }
}
</script>
<?php
    }
    function printUserHeader($user, $ecad_php_version){
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        echo '<html xmlns="http://www.w3.org/1999/xhtml">';
        echo '<head>';
        echo '<title>'.$ecad_php_version.'</title>';
        echo '</head>';
        echo '<body>';
        
        echo $ecad_php_version." &nbsp&nbsp&nbsp    user: ".$user.'&nbsp&nbsp&nbsp&nbsp&nbsp <a href="index.php?action=logout">  logout </a></br>';
    }
    function printUserPath($path){
        if ($path == '/'){
            echo "path: ";
            echo'<a href="'.'">root</a><a> /</a>';
        }else{
            $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?path=/";
            
            echo "path: ";
            $path_array = split('/',$path);
            
            echo'<a href="'.$newpath.'">root</a><a> /</a>';
            
            for ($path_part = 1; $path_part <= (count($path_array)-2); $path_part++) {
                $newpath = $newpath.$path_array[$path_part].'/';
                if ($path_part ==(count($path_array)-2)){
                    echo '<a> </a>'.'<a href="'.$newpath.'">'.$path_array[$path_part].'</a><a> /</a>';
                }else{
                    echo '<a> </a>'.'<a href="'.$newpath.'">'.$path_array[$path_part].'</a><a> /</a>';
                }
            }
        }
        echo'</br>';
    }
    function printFileSize($fullpath, $file){
        if(is_file($fullpath.'/'.$file))
        {
            if(round((filesize($fullpath.'/'.$file)/1000.000),3)>1000.000){
                echo round((filesize($fullpath.'/'.$file)/1000.000/1000),3)."MB   ";
            }else{
                echo round((filesize($fullpath.'/'.$file)/1000.000),3)."kb   ";
            }
        }
        else
        {
            echo ("Folder   ");
        }
    }
    function printFileEditUploadDeleteCreateButtons($can_delete, $can_upload){
        echo "\r\n".'<form method="POST" action="" enctype="multipart/form-data">';
        if($can_delete){ echo '<input name="rename_FolderOrFile" value="rename" type="submit"> <input name="delete_FolderOrFile" value="delete" type="submit"> <input name="create_Folder" value="new folder" type="submit">';}
        if($can_upload){ echo '<button type="button" onclick="showUploadFunction()">upload</button>';}
    }
    function printFileAndInfo($file , $nichtgelisteteDatein, $path, $can_delete, $fullpath){
        if (in_array ( $file , $nichtgelisteteDatein )){
            return 0;
            //files that are not listed for users
        }else{
            echo "\r\n";
            $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
            //makes a checkbox if user can delete files
            if($can_delete){ echo '<input type="checkbox" name='."'".'file_'.$file_in_html.''."'".' value="true"></input> ';}
            
            //writes the filesize of the given file in human readeble form
            printFileSize($fullpath, $file);
            
            //path system for shown files and folders
            $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?path=".$path;
            
            echo '<a href="'.$newpath.$file.'">'.$file."       ".'</a> </br>';
        }
        return 1;
    }
    //end of User interface Printer -----------------------------------------------------------------------------------------------------------------------------------
?><?php
    //user functions for (upload, download, create file, delete, change name) -----------------------------------------------------------------------------------------
    function upload_single_file($datarootpath, $log_fileUpload, $fullpath, $path, $maximalUploadSize, $maximalUploadSize){
        ini_set ( 'post_max_size' , $maximalUploadSize );
        ini_set ( "upload_max_filesize" , $maximalUploadSize );
        //echo ini_get('post_max_size');
        $target_dir = $fullpath;//"uploads/";
        //echo $target_dir;
        if(isset($_POST["upload_single_file"])) {
            $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
            if (file_exists($target_file)) {
                echo "A file with the same name allready exists</br>";
            }else{
                //echo $target_file;
                //echo basename($_FILES["fileToUpload"]["tmp_name"]);
                move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
                if(file_exists($target_file)){
                    //file sucessfully uploaded
                   echo "file: &nbsp&nbsp".basename($_FILES["fileToUpload"]["name"])."&nbsp&nbsp uploaded to: ".$path."</br>";
                    if($log_fileUpload){
                        ecad_php_log($datarootpath,"INFO","file uploaded ".'['.$path.basename($_FILES["fileToUpload"]["name"]).']['.filesize($target_file).'bytes]');
                    }
                   }else{
                       //file upload couldnt be completed
                   echo "!!ERROR!! there was a problem uplaoding the file!!!!</br>";
                       if($log_fileUpload){
                           ecad_php_log($datarootpath,"ERROR","upload error!! (file not found in destination) ".'['.$path.basename($_FILES["fileToUpload"]["name"]).']['.filesize($target_file).'bytes]');
                       }
                   }
            }
        }
    }
    function delete_FolderOrFile_submit($nichtgelisteteDatein, $fullpath){
        //get files of current folder
        $files = scandir($fullpath.'/');
        echo "Deleted files / folders:</br>";
        foreach($files as $file){
            if (in_array ( $file , $nichtgelisteteDatein )){
                
            }else{
                $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
                //echo $edit_file_if;
                if(isset($_POST[('file_'.$file_in_html)]) ){
                    echo $_POST[('file_'.$file_in_html)]."</br>";
                    $new_filename = $_POST[('file_'.$file_in_html)];
                    $new_filename = str_replace ("..\\" , " " , $new_filename);
                    $new_filename = str_replace ("../" , " " , $new_filename);
                    $new_filename = trim ($new_filename ," \t\n\r\0\x0B" );
                    
                    //delete folowing file..
                    //str_replace ("//" , "/" , $fullpath).$new_filename;
                    if(is_dir(str_replace ("//" , "/" , $fullpath).$new_filename)){
                        //deleteDir(str_replace ("//" , "/" , $fullpath).$new_filename);
                        rrmdir(str_replace ("//" , "/" , $fullpath).$new_filename);
                    }else{
                        unlink(str_replace ("//" , "/" , $fullpath).$new_filename);
                    }
                }
            }
        }
    }
    function delete_FolderOrFile($nichtgelisteteDatein, $fullpath){
        //get files of current folder
        $files = scandir($fullpath.'/');
        echo "really delete the following Items?</br>";
        echo "\r\n".'<form method="POST" action="">';
        $files_to_edit_counter = 0;
        foreach($files as $file){
            if (in_array ( $file , $nichtgelisteteDatein )){
                
            }else{
                $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
                $edit_file_if = (isset($_POST[('file_'.$file_in_html)]) && $_POST[('file_'.$file_in_html)]  ? "true" : "false");
                
                if(isset($_POST[('file_'.$file_in_html)]) ){
                    
                    echo "".'<input type="text" name="'.'file_'.$file_in_html.'" value="'.$file.'"></input>'.'<br/>';
                    $files_to_edit_counter++;
                }
            }
        }
        if($files_to_edit_counter > 0){
            echo '<input name="delete_FolderOrFile_submit" value="Yes" type="submit"><input name="" value="Abort" type="submit"></form>';
        }else{
            $show_user_interface = true;
            echo "no item selected! </br>";
        }
    }
    function rename_FolderOrFile_submit($nichtgelisteteDatein, $fullpath){
        //get files of current folder
        $files = scandir($fullpath.'/');
        foreach($files as $file){
            if (in_array ( $file , $nichtgelisteteDatein )){
                
            }else{
                $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
                //echo $edit_file_if;
                if(isset($_POST[('file_'.$file_in_html)]) ){
                    echo $file." changed to --> ".$_POST[('file_'.$file_in_html)]."</br>";
                    $new_filename = $_POST[('file_'.$file_in_html)];
                    $new_filename = str_replace ("..\\" , " " , $new_filename);
                    $new_filename = str_replace ("../" , " " , $new_filename);
                    $new_filename = trim ($new_filename ," \t\n\r\0\x0B" );
                    rename (str_replace ("//" , "/" , $fullpath).''.$file, str_replace ("//" , "/" , $fullpath).$new_filename);
                    //rename $file with $_POST[('file_'.str_replace (" " , "%20" , $file))]
                }
            }
        }
    }
    function rename_FolderOrFile($nichtgelisteteDatein, $fullpath){
        //get files of current folder
        $files = scandir($fullpath.'/');
        echo "\r\n".'<form method="POST" action="">';
        $files_to_edit_counter = 0;
        foreach($files as $file){
            if (in_array ( $file , $nichtgelisteteDatein )){
                
            }else{
                $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
                $edit_file_if = (isset($_POST[('file_'.$file_in_html)]) && $_POST[('file_'.$file_in_html)]  ? "true" : "false");
                
                if(isset($_POST[('file_'.$file_in_html)]) ){
                    
                    echo "".$file.'  -->    '.'<input type="text" name="'.'file_'.$file_in_html.'" value="'.$file.'"></input><br/>';
                    $files_to_edit_counter++;
                }
            }
        }
        if($files_to_edit_counter > 0){
            echo '<input name="rename_FolderOrFile_submit" value="submit" type="submit"></form>';
        }else{
            $show_user_interface = true;
            echo "please select at a object </br>";
        }
    }
    function create_Folder($fullpath){
        $counter = 0;
        $new_folder_created = false;
        do{
            if($counter  == 0){
                if(!file_exists($fullpath.'/New Folder') && !is_dir($fullpath.'/New Folder')) {
                    mkdir($fullpath.'/New Folder', 0777, true);
                    $new_folder_created = true;
                }else{
                    $counter++;
                }
            }else{
                
                if((!file_exists($fullpath.'/New Folder ('.$counter.')') && !is_dir($fullpath.'/New Folder ('.$counter.')'))) {
                    mkdir($fullpath.'/New Folder ('.$counter.')', 0777, true);
                    $new_folder_created = true;
                }else{
                    $counter++;
                }
            }
        }while(!$new_folder_created);
        echo 'Created new Folder!<br/>';
    }
    function makeDownload($fullpath, $path){
        //do download
        
        
        $filename = substr($path, strrpos($path, '/') + 1);
        
        substr($path, strrpos($path, '/') + 1);
        
        makeDownloadHead($fullpath, filetype($fullpath),$filename);
        
        //clean the file reader
        ob_end_clean();
        //read file for download
        readfile($fullpath);

    }
?><?php
    //get requested path safe of escape characters
    function getSafePath($datarootpath, $userpath){
        //get path and validate
        $path = $_GET["path"];
        $path = str_replace ("%20" , " " , $path);
        
        
        //remove escape characters
        $fullpath = $datarootpath.$userpath.$path;
        //validate path
        if (strlen($path) >0){
            if ($path[0] != "/"){
                $path ="/".$path;
            }
        }
        $fullpath = $datarootpath.$userpath.$path;
        //new--
        $fullpath = str_replace ("\\.." , "" , $fullpath);
        $fullpath = str_replace ("..\\" , "" , $fullpath);
        $fullpath = str_replace ("/.." , "" , $fullpath);
        $fullpath = str_replace ("../" , "" , $fullpath);
        //-----
        //-----------------------------------------------------------------
        if(!is_file($fullpath)){
            if (strlen($path) >0){
                if ($path[0] != "/"){
                    $path ="/".$path;
                }
                if ($path[strlen($path) - 1] != "/"){
                    $path = $path."/";
                }
            }else{
                $path = "/";
            }
            $path = str_replace (".." , "" , $path);
            $fullpath = $datarootpath.$userpath.$path;
        }
        //-----------------------------------------------------------------
        return $path;
    }
    
    function getSafeFullPath($datarootpath, $completeUserPath, $path){
        
        $fullpath = $datarootpath.$completeUserPath.$path;
        
        return $fullpath;
    }
    
?><?php
    //login system login function -----------------------------------------------------------------------------------------------------
    function handelLoginScreenView($show_ecad_php_version_on_title, $ecad_php_version, $datarootpath){
        if(isset($_POST))
        {
            
            if($show_ecad_php_version_on_title){
                echo "<html><head><title>".$ecad_php_version."</title></head><body>";
            }
            echo '<div style="text-align:center; margin= 0 auto;">';
            if($show_ecad_php_version_on_title){
                echo $ecad_php_version."</br></br>";
            }
            ?>
<form method="POST" action="index.php">
Username: <input type="text" name="user"></input><br/>
Password: <input type="password" name="pass"></input><br/>
<input type="submit" name="submit" value="login"></input>
</form>
</div>
<?php
    }
    if(isset($_POST['user'])){
        ecad_php_log($datarootpath,"WARNING","unsucessful login for ".'['.$_POST['user'].']');
        echo '<div style="text-align:center; margin= 0 auto;"><a>username or password incorect</a></div>';
    }
    echo '</body></html>';
    }
    
    function handelLoginAccepted($datarootpath, $user, $pass, $secret_word){
        //when login is accepted
        $newUserCockies = $user.','.md5($pass.$secret_word.time());
        //activate cockie
        $user_config_file_Standard = '<?php $acceptableuserLoginCockies = $acceptableuserLoginCockies."'.$newUserCockies.'-"; ?>';
        file_put_contents($datarootpath."/users/".$user.'/login.php', $user_config_file_Standard, FILE_APPEND);

        setcookie('ECAD_PHP_fileviewer_login',$newUserCockies);
        //setcookie('ECAD_PHP_fileviewer_login',$user.','.md5($pass.$secret_word));
        echo "you are logged in      please wait.......";
        header("Refresh:0; url=index.php?path=");
        ecad_php_log($datarootpath,"INFO","user logged in ".'[new cockie: '.$newUserCockies.']');

    }

function removeLoginCockieFromServer($datarootpath, $c_username){
    //delete session from server
    $str3706849=file_get_contents($datarootpath."/users/".$c_username.'/login.php');
    
    
    $str3706849=str_replace('<?php $acceptableuserLoginCockies = $acceptableuserLoginCockies."'.$_COOKIE['ECAD_PHP_fileviewer_login'].'-"; ?>', '',$str3706849);
    
    file_put_contents($datarootpath."/users/".$c_username.'/login.php', $str3706849);
    
    //delete cockie from client
    setcookie('ECAD_PHP_fileviewer_login',"null");
    header("Refresh:0; url=index.php");
}

?><?php
    //administrator functions ----------------------------------------------------------------------------------------------------------
    function printsAdministratorInterfaceHeader($ecad_php_version, $user){
        //admin user Interface
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        echo '<html xmlns="http://www.w3.org/1999/xhtml">';
        echo '<head>';
        echo '<title>'.$ecad_php_version.'</title>';
        echo '</head>';
        echo '<body>';
        
        echo $ecad_php_version.'    <a href="index.php?action=logout"> logout </a></br>';
        echo "user: ".$user." (you are adminnistrator)</br>";
    }
    
    
    function showUserListPannel($datarootpath, $canAccessSystemFolder){
        echo '</br>users:</br>';
        
        $files = scandir($datarootpath.'/users/');
        sort($files); // this does the sorting
        
        $datein = 0;
        $nichtgelisteteDatein = array("index.php", ".htaccess", ".", "..");
        foreach($files as $file){
            if (in_array ( $file , $nichtgelisteteDatein )){
            }else{
                if ($file == "admin"){
                    echo'<form method="POST" action="">'.$file.'<span style="padding-left:80px"></span>   <input type="hidden" name="user_to_delete" value="'.$file.'"><input name="edit_user" value="edit" type="submit"><span title="all sessions of this user will be closed"><input name="logout_user" value="logout" type="submit"></span></form>';
                }else{
                    echo'<form method="POST" action="">'.$file.'<span style="padding-left:80px"></span>   <input type="hidden" name="user_to_delete" value="'.$file.'"><input name="edit_user" value="edit" type="submit"><span title="the user and all his files will be deleted"><input name="delete_user" value="delete" type="submit"></span><span title="all sessions of this user will be closed"><input name="logout_user" value="logout" type="submit"></span></form>';
                }
            }
        }
        echo'</br><form method="POST" action=""><input name="create_user" value="new user" type="submit"></form>';
        echo '</br></br><a href="index.php?action=getLogFile"> download log file </a>';
        if($canAccessSystemFolder){
            echo '<span style="padding-left:80px"></span><a href="index.php?path=/"> root file browser </a>';
        }
        
    }
    function logout_user($datarootpath){
        if($_POST['user_to_delete'] != ""){
            ecad_php_log($datarootpath,"INFO"," logged out by admin".'['.$_POST['user_to_delete'].']');
            $ecad_php_user_config_file = fopen($datarootpath.'/users/'.$_POST['user_to_delete'].'/login.php', "w");
            $user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
            fwrite($ecad_php_user_config_file, $user_config_file_Standard);
            fclose($ecad_php_user_config_file);
        }
    }
function edit_user_submit($datarootpath, $secret_word){
    if($_POST['username'] != "" && file_exists($datarootpath."/users/".$_POST['username'])){
        $current_administrative_user = $user;
        //standard if userconfig not found
        $canAccessSystemFolder = false;
        $userIsAdmin = false;
        include $datarootpath."/users/".$_POST['username']."/userconfig.php";
        
        if ($_POST['password'] ==""){
            edit_user_keep_password($_POST['username'],$userpasswordHash,$datarootpath,$secret_word,(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false"),(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false"),(isset($canAccessSystemFolder) && $canAccessSystemFolder ? "true" : "false"));
            
        }else{
            edit_user($_POST['username'],$_POST['password'],$datarootpath,$secret_word,(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false"),(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false"),(isset($canAccessSystemFolder) && $canAccessSystemFolder ? "true" : "false"));
        }
        include $datarootpath."/users/".$current_administrative_user."/userconfig.php";
        $userIsAdmin = true;
    }
}
function printEditUserView($datarootpath){
    //store curent user
    $current_administrative_user = $user;
    //load to edit user
    include $datarootpath."/users/".$_POST['user_to_delete']."/userconfig.php";
    $toeditUser_can_upload = $can_upload;
    $toeditUser_can_delete = $can_delete;
    //load administrator back in again
    include $datarootpath."/users/".$current_administrative_user."/userconfig.php";
    
    echo '</br><form method="POST" action="">Username: <input type="text" name="username" value="'.$_POST['user_to_delete'].'" readonly></input><br/>Password: <input type="text" name="password">(left empty to keep password)</input><br/>';
    
    if ($toeditUser_can_upload){
        echo 'can upload:<input type="checkbox" name="can_upload" value="can_upload" checked></br>';
    }else{
        echo 'can upload:<input type="checkbox" name="can_upload" value="can_upload"></br>';
    }
    
    if ($toeditUser_can_delete){
        echo 'can delete / edit:<input type="checkbox" name="can_delete" value="can_delete" checked></br>';
    }else{
        echo 'can delete / edit:<input type="checkbox" name="can_delete" value="can_delete"></br>';
    }
    echo '<input name="edit_user_submit" value="OK" type="submit"></form>';
}
function printCreateUserView(){
    echo '</br><form method="POST" action="">Username: <input type="text" name="username"></input><br/>Password: <input type="text" name="password"></input><br/>';
    echo 'can upload:<input type="checkbox" name="can_upload" value="is admin"></br>';
    echo 'can delete / edit:<input type="checkbox" name="can_delete" value="is admin"></br>';
    echo '<input name="create_user_submit" value="OK" type="submit"></form>';
}
function downloadLogFile($datarootpath){
    ecad_php_log($datarootpath,"INFO","Log File was downlaoded ");
    
    $filename = "/ecadPHPLog.log";
    
    makeDownloadHead($datarootpath.$filename, filetype($datarootpath.$filename),"ecadPHPLog.log");
    
    //clean the file reader
    ob_end_clean();
    //read file for download
    readfile($datarootpath.$filename);
}


?>