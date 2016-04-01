<?php
    //change in the folowing only in the config.php file!!!
    $debug = false;
    $secret_word = "word";
    $ecad_php_version ="ECAD PHP file hub v0.2.03";
    $ecad_php_version_number = "v0.2.03";
    $ecad_php_version_id = 100;
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
    
    //create test share:
    //createNewShareFunction($datarootpath, "admin", "debug share");
    //addUserToShareSubmit($datarootpath, "admin", "share id", "user0", "false", "false", "false", "false");
    
    
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
                create_user($_POST['username'],$_POST['password'],$datarootpath,$secret_word,(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false"),(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false"),$_POST['allowed_shares']);
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
    
    //Normal user handling-----------------------------------------------------------------------------------------------------------------------------------------------------------------------
    if($userIsAdmin == false or $showFileViewer){
        //normal user logged in or administrator is showing file viewer
        //check if normal path or share
        if(isset($_GET["share"]) && !$canAccessSystemFolder){
            $sharepath = $_GET["share"];
            //share handling ---------------------------------------------------------------------------------------------------------------------------------------------------------------
            if($sharepath == "" or $sharepath == "/"){
                echo $ecad_php_version." &nbsp&nbsp&nbsp    user: ".$user.' <span style="padding-left:20px"></span><a href="index.php?userpanel">user panel</a><span style="padding-left:30px"></span> <a href="index.php?action=logout">  logout </a></br>';
                //echo '<a href="index.php?path=/"><-- back to my files</a></br>';
                //share selection-----------------------------------------------------------------
                $showUserInterface = true;
                
                if ( isset( $_POST['edit_share'] ) ) {
                    $showUserInterface = false;
                    echo '<a href="index.php?share"><-- back to share overview</a></br>';
                    editShareInterface($datarootpath, $user, $_POST['shareToEdit']);
                }
                
                
                
                if(isset( $_POST['share_createNew'])){
                    $showUserInterface = false;
                    createShareView();
                }
                if(isset( $_POST['createNewShareSubmit'])){
                    //create new share from create new share View data
                    $shareName = getSafeString($_POST['share_name']);
                    $usersToAdd = $_POST['usersInShare'];
                    $shareCanUpload = $_POST[('can_upload')]  ? "true" : "false";
                    $shareCanDelete = $_POST[('can_delete')]  ? "true" : "false";
                    $shareCanDownload = $_POST[('can_download')]  ? "true" : "false";
                    $shareCanAddUsers = $_POST[('can_addUsers')]  ? "true" : "false";
                    
                    //(isset($_POST[('')]) && $_POST[('')]  ? "true" : "false")
                    if(countMyShares($datarootpath, $user) < $amountOfAllowedShares){
                        createNewShareSubmit($datarootpath, $user, $shareName, $usersToAdd, $shareCanUpload, $shareCanDelete, $shareCanDownload, $shareCanAddUsers);
                    }else{
                        echo "you cant create any more shares!!!!</br></br>";
                    }
                    
                }
                
                    //edit share view submit
                if(isset( $_POST['edit_user_in_share'])){
                    //edit user in share view
                    $showUserInterface = false;
                    editUserInShareView($datarootpath, $user);
                }
                if(isset( $_POST['editUserInShareSubmit'])){
                    //edit user in share view submit
                    $showUserInterface = false;
                    editUserInShareSubmit($datarootpath, $user);
                    editShareInterface($datarootpath, $user, $_POST['shareToEdit']);
                }
                
                
                
                if(isset( $_POST['remove_user_in_share'])){
                    //remove user from share view
                    //<input name="shareToEdit" value="'.$shareID.'" type="hidden"><input name="shareUser" value="'.$userInShare.'" type="hidden">'
                    removeUserFromShareView($datarootpath, $user);
                    $showUserInterface = false;
                }
                if(isset( $_POST['remove_user_in_share_Submit'])){
                    //remove user from share view submit
                    removeUserFromShareSubmit($datarootpath, $user);
                    $showUserInterface = false;
                    editShareInterface($datarootpath, $user, $_POST['shareToEdit']);
                }

                
                
                if(isset( $_POST['add_user_to_share'])){
                    //add user to share view
                    $showUserInterface = false;
                    addUserToShareView($_POST['shareToEdit']);
                }
                if(isset( $_POST['addUsersToShareSubmit'])){
                    //add user to share view submit
                    addUsersToShareSubmit($datarootpath, $user);
                    
                    //show share
                    $showUserInterface = false;
                    echo '<a href="index.php?share"><-- back to share overview</a></br>';
                    editShareInterface($datarootpath, $user, $_POST['shareToEdit']);
                }
                
                if(isset( $_POST['delete_share'])){
                    //delete share view
                    deleteShareView($datarootpath, $user);
                    //show share
                    $showUserInterface = false;
                }
                if(isset( $_POST['deleteShareSubmit'])){
                    //delete share submit
                    deleteShareSubmit($datarootpath, $user);
                }
                
                
                if($showUserInterface){
                    printUserInterfaceShareSelection($datarootpath, $user, $amountOfAllowedShares);
                }

                
            }else{
                //share selected
                //echo "share selected:</br>";
                $shareID = getSafeShareID();
                //check if share exists on this user
                if(is_dir($datarootpath.'/users/'.$user.'/shares/'.$shareID)){
                    $show_user_interface = true;
                    
                    //get information about share
                    include $datarootpath.'/users/'.$user.'/shares/'.$shareID.'/shareinfo.php';
                    //check if share does exist on owner
                    if(is_dir($datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/')){
                        //echo $datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/users/'.$user.'/userPermissions.php';
                        //get permission information about this user in share
                        include $datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/users/'.$user.'/userPermissions.php';
                        //get requested path
                        $sharepath = getSafeSharePath();
                        //get requested full path
                        $fullSharePath = getSafeFullSharePath($datarootpath, $shareCreatorName, $shareID, $sharepath);
                        //echo $fullSharePath;
                        //start of functions -----------------------------------------------------------------------
                        //check if file and prepare download
                        if(is_file(rtrim($fullSharePath, '/'))){
                            //check if permission to downlaod
                            if(($shareCanDownload == 'true')||($user == $shareCreatorName)){
                                //download file if path is a file
                                ecad_php_log($datarootpath,"INFO","file download from share [".$shareID.$sharepath."]");
                                makeDownload(rtrim($fullSharePath, '/'), rtrim($sharepath, '/'));
                            }else{
                                echo "</br></br>you are not allowed to downlaod!!!!";
                            }
                        }else{
                            //functions:
                            //create new folder in share
                            if ( isset( $_POST['create_Folder'] ) && (($shareCanDelete == 'true')||($user == $shareCreatorName)) ) {
                                $show_user_interface = true;
                                create_Folder($fullSharePath);
                            }
                            
                            //show rename folder / file form for share
                            if ( isset( $_POST['rename_FolderOrFile'] ) && (($shareCanDelete == 'true')||($user == $shareCreatorName)) ) {
                                $show_user_interface = false;
                                rename_FolderOrFile($nichtgelisteteDatein, $fullSharePath);
                            }
                            //rename folder or file if user has comfirmed for share
                            if ( isset( $_POST['rename_FolderOrFile_submit'] ) && (($shareCanDelete == 'true')||($user == $shareCreatorName)) ) {
                                $show_user_interface = true;
                                rename_FolderOrFile_submit($nichtgelisteteDatein, $fullSharePath);
                            }
                            
                            //show form with delete file for sahre
                            if ( isset( $_POST['delete_FolderOrFile'] ) && (($shareCanDelete == 'true')||($user == $shareCreatorName)) ) {
                                $show_user_interface = false;
                                delete_FolderOrFile($nichtgelisteteDatein, $fullSharePath);
                            }
                            //deletes files if user has comfirmed deltion for share
                            if ( isset( $_POST['delete_FolderOrFile_submit'] ) && (($shareCanDelete == 'true')||($user == $shareCreatorName)) ) {
                                $show_user_interface = true;
                                delete_FolderOrFile_submit($nichtgelisteteDatein, $fullSharePath);
                            }
                            
                            //upload file if file is being uploaded
                            if ( isset( $_POST['upload_single_file'] ) && (($shareCanUpload == 'true')||($user == $shareCreatorName)) ) {
                                upload_single_file($datarootpath, $log_fileUpload, $fullSharePath, $sharepath, $maximalUploadSize, $maximalUploadSize);
                            }
                            //upload multiple files if file is being uploaded
                            if ( isset( $_POST['upload_multiple_file'] ) && $can_upload ) {
                                upload_multiple_file($datarootpath, $log_fileUpload, $fullSharePath, $sharepath, $maximalUploadSize, $maximalUploadSize);
                            }
                            
                            //download multiple files as zip archive for shares
                            if ( isset( $_POST['download_multiple'] ) ){
                                $show_user_interface = false;
                                $result = download_multiple_file($datarootpath, $user, $nichtgelisteteDatein, $fullSharePath);
                                if(!$result){
                                    $show_user_interface = true;
                                }
                            }
                            
                            //-----------

                            //echo "</br>shareID: ".$shareID;
                            //echo "</br>share path: ".$sharepath;
                            //echo "</br>full share path: ".$fullSharePath;
                            //echo"</br></br>";
                            
                            if($show_user_interface){
                                //echo '<a href="index.php?path=/"><-- back to normal files</a></br>';
                                if($user == $shareCreatorName){
                                    printUserInterfaceShareFileViewer($shareID, $user, $sharepath, $fullSharePath, $datarootpath, true, true, $nichtgelisteteDatein, $ecad_php_version, $shareName, $shareID);
                                }else{
                                    printUserInterfaceShareFileViewer($shareID, $user, $sharepath, $fullSharePath, $datarootpath, ($shareCanDelete == 'true'), ($shareCanUpload == 'true'), $nichtgelisteteDatein, $ecad_php_version, $shareName, $shareID);
                                }
                                
                            }
                        }
                    }else{
                        echo '<a href="index.php?userpanel"><-- back to user panel</a></br>';
                        echo 'the share no longer exists!!!';
                    }
                   }else{
                       echo '<a href="index.php?userpanel"><-- back to user panel</a></br>';
                       echo "share not found or no permissions";
                   }

            }
            
           
        }else if (isset($_GET["userpanel"])){
            //show user panel
            showUserPanel($datarootpath, $user, $ecad_php_version);
            
        }else{
            //normal path handling ----------------------------------------------------------------------------------------------------------------------------------------------------------
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
                //upload multiple files if file is being uploaded
                if ( isset( $_POST['upload_multiple_file'] ) && $can_upload ) {
                    upload_multiple_file($datarootpath, $log_fileUpload, $fullpath, $path, $maximalUploadSize, $maximalUploadSize);
                }
                //download multiple files as zip archive
                if ( isset( $_POST['download_multiple'] ) ){
                    $show_user_interface = false;
                    $result = download_multiple_file($datarootpath, $user, $nichtgelisteteDatein, $fullpath);
                    
                    if(!$result){
                        $show_user_interface = true;
                    }
                }
                
                //show user interface if nothing else has happend
                if($show_user_interface){
                    printUserInterfaceFileViewer($user, $path, $fullpath, $datarootpath, $can_delete, $can_upload, $nichtgelisteteDatein, $ecad_php_version);
                }
                
                
                //end of system for normal paths------------------------------------------------------------------------
            }
        }
        //end of system for logged in users------------------------------------------------------------------------
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
    //unsorted functions-------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    function getSafeString($text){
        
        $text = preg_replace('/[^a-z0-9]/i', '_', $text);
        
        return $text;
    }
    function getSafeFileName($text){
        
        $text = preg_replace('/[^a-z0-9\.\-]/i', '_', $text);
        
        return $text;
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
        $dataFolderName = '/ECAD PHP file hub data';
        //ecad php config file
        $ecadphpconfigfile = fopen("config.php", "w");
        $ecadphpconfigStandard = '<?php'."\r\n".'$datarootpath='."'".__DIR__.$dataFolderName."'".';'."\r\n".'$firstInstallationVersion='."'".$ecad_php_version_number."'".';'."\r\n".'$adminPassword="admin";'."\r\n".'?>'.'<?php'."\r\n".'$user='.'"user0";'."\r\n".'$userpath='.'"/user0";'."\r\n".'$log_fileUpload=true;'."\r\n".'?>';
fwrite($ecadphpconfigfile, $ecadphpconfigStandard);
fclose($ecadphpconfigfile);
//ecad php data folder
mkdir('.'.$dataFolderName.'/shares', 0777, true);
mkdir('.'.$dataFolderName.'/pages', 0777, true);
mkdir('.'.$dataFolderName.'/users', 0777, true);

//create user0
create_user("user0","admin",'.'.$dataFolderName.'/',$secret_word, "false", "false","0");
//create user0 test folder
mkdir('.'.$dataFolderName.'/users/user0/data/test', 0777, true);


//create admin
create_user("admin","admin",'.'.$dataFolderName.'/',$secret_word, "true", "true","0");
//make changes for admin
$ecad_php_user_config_file = fopen('.'.$dataFolderName.'/users/admin/userconfig.php', "w");
$user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5("admin".$secret_word)."'".';'."\r\n".'$userIsAdmin= true;'."\r\n".'$can_upload= true;'."\r\n".'$can_delete= true;'."\r\n".'$canAccessSystemFolder= true;'.'$amountOfAllowedShares=0;'.'?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);


//configurate htaccess
$ecad_php_htaccess_file = fopen('.'.$dataFolderName.'/.htaccess', "w");
$ecad_php_htaccess_file_Standard = '<Directory ./>'."\r\n".'Order deny,Allow'."\r\n".'Deny from all'."\r\n".'</Directory>';
fwrite($ecad_php_htaccess_file, $ecad_php_htaccess_file_Standard);
fclose($ecad_php_htaccess_file);

//config htaccess in root folder
file_put_contents(".htaccess","\r\nphp_value upload_max_filesize 50M\r\nphp_value post_max_size 50M",FILE_APPEND);

ecad_php_log(__DIR__.''.$dataFolderName.'',"INFO","ECAD PHP fileviewer successfully installed");
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
    function create_user($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word,$toeditUser_can_upload,$toeditUser_can_delete,$amountOfAllowedShares){
        ecad_php_log($ECAD_PHP_fileviewer_X_data_folder,"INFO","user created ".'['.$toCreateUsername.']');
        //create user folders
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername);
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/data');
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/downloadpreperation');
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5($toCreateUserPassword.$secret_word)."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload='.$toeditUser_can_upload.";\r\n".'$can_delete='.$toeditUser_can_delete.";\r\n".'$canAccessSystemFolder=false;'.'$amountOfAllowedShares='.$amountOfAllowedShares.';?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);
        //create user cockie file
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/login.php', "w");
        $user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);

        //create user share data
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/sharemounts');
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/shares');

    }
?><?php
    function edit_user($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word,$toeditUser_can_upload,$toeditUser_can_delete,$canAccessSystemFolder,$amountOfAllowedShares){
        ecad_php_log($ECAD_PHP_fileviewer_X_data_folder,"INFO","user edited ".'['.$toCreateUsername.']');
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5($toCreateUserPassword.$secret_word)."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload='.$toeditUser_can_upload.";\r\n".'$can_delete='.$toeditUser_can_delete.";\r\n".'$canAccessSystemFolder='.$canAccessSystemFolder.";\r\n".'$amountOfAllowedShares='.$amountOfAllowedShares.";\r\n".'?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);
        //create user cockie file
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/login.php', "w");
        $user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);

}
?><?php
    function edit_user_keep_password($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word,$toeditUser_can_upload,$toeditUser_can_delete,$canAccessSystemFolder,$amountOfAllowedShares){
        ecad_php_log($ECAD_PHP_fileviewer_X_data_folder,"INFO","user edited (password kept) ".'['.$toCreateUsername.']');
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".$toCreateUserPassword."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload='.$toeditUser_can_upload.";\r\n".'$can_delete='.$toeditUser_can_delete.";\r\n".'$canAccessSystemFolder='.$canAccessSystemFolder.";\r\n".'$amountOfAllowedShares='.$amountOfAllowedShares.";\r\n".'?>';
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
            //greater than one because of the . foder
            if(count(array_diff($files, $nichtgelisteteDatein)) > 1){
                //make select all files checkbox
?>
<input type="checkbox" name="action_toggleCheckboxSelection" value="true" onClick="toggle_file_checkboxes(this)"></input> </br>
                
<script language="JavaScript">
function toggle_file_checkboxes(source) {
    //checkboxes = document.getElementsByID('id_file_checkbox');
    checkboxes = document.getElementsByClassName('id_file_checkbox');
    for(var i=0, n=checkboxes.length;i<n;i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

<?php
            }
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
<!--
old single file uploader
<input type="file" name="fileToUpload" id="fileToUpload">
<input type="submit" value="Upload File" name="upload_single_file">
<form action="" method="post" enctype="multipart/form-data">
-->


<input type="file" id="file" name="filesToUpload[]" multiple="multiple" />
<input type="submit" value="Upload Files" name="upload_multiple_file" />

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
        
        echo $ecad_php_version." &nbsp&nbsp&nbsp    user: ".$user.' <span style="padding-left:20px"></span><a href="index.php?userpanel">user panel</a><span style="padding-left:30px"></span> <a href="index.php?action=logout">  logout </a></br>';
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
        echo '<input name="download_multiple" value="download" type="submit">';
        if($can_delete){ echo ' <input name="rename_FolderOrFile" value="rename" type="submit"> <input name="delete_FolderOrFile" value="delete" type="submit"> <input name="create_Folder" value="new folder" type="submit">';}
        if($can_upload){ echo ' <button type="button" onclick="showUploadFunction()">upload</button>';}
    }
    function printFileAndInfo($file , $nichtgelisteteDatein, $path, $can_delete, $fullpath){
        if (in_array ( $file , $nichtgelisteteDatein )){
            return 0;
            //files that are not listed for users
        }else{
            echo "\r\n";
            $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
            //makes a checkbox if user can delete files
            //if($can_delete){ echo '<input type="checkbox" name="file_'.$file_in_html.'" value="true"></input> ';}
            echo '<input type="checkbox" name="file_'.$file_in_html.'" value="true" class="id_file_checkbox"></input> ';
            
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
        echo 'uploading single files is no longer supported!!';
        /*
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
         */
    }
    function upload_multiple_file($datarootpath, $log_fileUpload, $fullpath, $path, $maximalUploadSize, $maximalUploadSize){
        
        //set php parameters
        ini_set ( 'post_max_size' , $maximalUploadSize );
        ini_set ( "upload_max_filesize" , $maximalUploadSize );
        //-----
        $count = 0;
        foreach ($_FILES['filesToUpload']['name'] as $f => $name) {
            if ($_FILES['filesToUpload']['error'][$f] == 4) {
                echo "!!ERROR!! (upload error: 4)</br>";
                continue; // Skip file if any error found
            }
            if ($_FILES['filesToUpload']['error'][$f] == 0) {
                /*
                if ($_FILES['filesToUpload']['size'][$f] > 0) {
                    echo '!!ERROR!! File to large';
                    continue; // Skip large files
                }
                 */
                /*
                if( ! in_array(pathinfo($name, PATHINFO_EXTENSION), $valid_formats) ){
                    echo "!!ERROR!! $name is not a valid format";
                    continue; // Skip invalid file formats
                }
                 */
                if(false){}else{ // No error found! Move uploaded files
                    //get safe file name
                    $name = getSafeFileName($name);
                    
                    if(file_exists($fullpath.'/'.$name)){
                        echo "A file with the same name allready exists ( $name )</br>";
                    }
                    else if(move_uploaded_file($_FILES["filesToUpload"]["tmp_name"][$f], $fullpath.'/'.$name)){
                        echo "file: &nbsp&nbsp".basename($_FILES["fileToUpload"]["name"])."&nbsp&nbsp uploaded to: ".$path."</br>";
                        $count++; // Number of successfully uploaded file
                    }else{
                        echo "!!ERROR!! there was a problem uplaoding the file: $name </br>";
                    }
                }
            }
        }
        
    }
    
    function download_multiple_file($datarootpath, $user, $nichtgelisteteDatein, $fullpath){
        //get files of current folder
        $files = scandir($fullpath.'/');
        $zippingLog = array();
        array_push($zippingLog, 'starting zipping');
        //echo 'Preparing download . . . . ';
        $downloadFiles = array();
        $files_to_edit_counter = 0;
        foreach($files as $file){
            //echo "file: $file </br>";
            if (in_array ( $file , $nichtgelisteteDatein )){
                //echo "not downloading the file: $file </br>";
            }else{
                $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
                $edit_file_if = (isset($_POST[('file_'.$file_in_html)]) && $_POST[('file_'.$file_in_html)]  ? "true" : "false");
                if(isset($_POST[('file_'.$file_in_html)]) ){
                     array_push($zippingLog, 'file: '.$file);
                    //echo $file;
                    array_push($downloadFiles, $file);
                    $files_to_edit_counter++;
                }
            }
        }
        if($files_to_edit_counter > 0){

            //$zipname = 'file.zip';
            $zipname = 'File_Export '.date("Y-m-d.H-i-s.U").'.zip';
            $zip = new ZipArchive();
            $source = $datarootpath.'/'.$zipname;
            if ($zip->open($datarootpath.'/'.$zipname, ZipArchive::CREATE)!==TRUE) {
                array_push($zippingLog, "cannot open <$filename>");
                exit("cannot open <$filename>\n");
            }
            
            foreach($downloadFiles as $file){
                
                $source = $fullpath.'/'.$file;
                //echo '</br></br>';
                //$source = $fullpath;
                $source = str_replace('\\', '/', realpath($source));
                $source = str_replace('//', '/', realpath($source));
                $source = str_replace('\\', '/', realpath($source));
                
                $fullpath = str_replace('\\', '/', realpath($fullpath));
                $fullpath = str_replace('//', '/', realpath($fullpath));
                $fullpath = str_replace('\\', '/', realpath($fullpath));
                
                array_push($zippingLog, 'source: '.$source);
                //echo 'source: '.$source . '/'.'</br>';
                array_push($zippingLog, 'fullpath: '.$fullpath);
                //echo 'fullpath: '.$fullpath.'</br>';
                //check if folder or file
                if (is_dir($source) === true)
                {
                    
                    $filesZIP = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
                    
                    
                    foreach ($filesZIP as $fileZIP)
                    {
                        
                        $fileZIP = str_replace('\\', '/', $fileZIP);
                        $fileZIP = str_replace('//', '/', $fileZIP);
                        
                        //echo 'zipPath: '.str_replace($source . '/', '', $fileZIP . '/').'</br>';
                        $fileZIP = realpath($fileZIP);
                        
                        $fileZIP = str_replace('\\', '/', $fileZIP);
                        $fileZIP = str_replace('//', '/', $fileZIP);
                        array_push($zippingLog, 'fileZIP: '.$fileZIP);
                        //echo 'fileZIP: '.$fileZIP.'</br>';
                        array_push($zippingLog, 'zipPath: '.str_replace($source, '', $fileZIP . '/'));
                        //echo 'zipPath: '.str_replace($source, '', $fileZIP . '/').'</br>';
                        
                        
                        
                        // Ignore "." and ".." folders
                        if( in_array(substr($fileZIP, strrpos($fileZIP, '/')+1), array('.', '..')) )
                            continue;
                        
                        //$fileZIP = realpath($fileZIP);
                        if (is_dir($fileZIP) === true)
                        {
                            //ausname für root directory
                            if ($fileZIP != $fullpath){
                                array_push($zippingLog, 'addingDIR: '.$fileZIP.'('.$file.str_replace($source , '', $fileZIP . '/').')');
                                //echo 'addingDIR: '.$fileZIP.'('.$file.str_replace($source , '', $fileZIP . '/').')</br>';
                                $zip->addEmptyDir($file.str_replace($source , '', $fileZIP . '/'));
                            }

                        }
                        else if (is_file($fileZIP) === true)
                        {
                            array_push($zippingLog, 'addingFileInsideFolder: '.$fileZIP.'('.$file.str_replace($source , '', $fileZIP).')');
                            //echo 'addingFileInsideFolder: '.$fileZIP.'('.$file.str_replace($source , '', $fileZIP).')</br>';
                            $zip->addFromString($file.str_replace($source , '', $fileZIP), file_get_contents($fileZIP));
                        }
                    }
                }
                else
                {
                    array_push($zippingLog,'addingFile:'.$fileZIP);
                    //echo 'addingFile:'.$fileZIP.'</br>';
                    $zip->addFromString(basename($source), file_get_contents($source));
                }
            }
            
            
            //}
            //close zip
            $zip->close();
            //send the zip
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename="'.$zipname.'"');
            header('Content-Length: ' . filesize($datarootpath.'/'.$zipname));
            
            readfile($datarootpath.'/'.$zipname);
            
            ecad_php_log($datarootpath,"INFO","files downloaded (ZIP archive) ".'['.filesize($datarootpath.'/'.$zipname).'bytes]['.$zipname.']');
            
            //delete zip from server
            unlink($datarootpath.'/'.$zipname);
            
            return true;
            
        }else{
            $show_user_interface = true;
            echo "no item selected! </br>";
            return false;
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
            edit_user_keep_password($_POST['username'],$userpasswordHash,$datarootpath,$secret_word,(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false"),(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false"),(isset($canAccessSystemFolder) && $canAccessSystemFolder ? "true" : "false"),$_POST['allowed_shares']);
            
        }else{
            edit_user($_POST['username'],$_POST['password'],$datarootpath,$secret_word,(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false"),(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false"),(isset($canAccessSystemFolder) && $canAccessSystemFolder ? "true" : "false"),$_POST['allowed_shares']);
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
    echo 'amount of allowed shares: <input type="text" name="allowed_shares" value="'.$amountOfAllowedShares.'"></input> (10 recomendet)</br>';
    
    echo '<input name="edit_user_submit" value="OK" type="submit"></form>';
}
function printCreateUserView(){
    echo '</br><form method="POST" action="">Username: <input type="text" name="username"></input><br/>Password: <input type="text" name="password"></input><br/>';
    echo 'can upload:<input type="checkbox" name="can_upload" value="is admin"></br>';
    echo 'can delete / edit:<input type="checkbox" name="can_delete" value="is admin"></br>';
    echo 'amount of allowed shares: <input type="text" name="allowed_shares" value="0"></input> (10 recomendet)</br>';
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


?><?php
//sharing system functions ---------------------------------------------------------------------------------------------------------------------
    //interface share owerview
    function printUserInterfaceShareSelection($datarootpath, $user, $userMaximumShares){
        //echo "user: ".$user;
        echo 'share selection menu';
        echo "</br>";
        //echo "</br>";
        $sharesPath = $datarootpath.'/users/'.$user.'/shares/';
        $files = scandir($sharesPath);
        //echo "shares:</br>";
        $mySharesList = "";
        $mySharesCounter = 0;
        $otherShareList = "";
        $otherSharesCounter = 0;
        
        $brokenShareList = "";
        $brokenShareCount = 0;
        foreach($files as $file){
            if($file != "." && $file != ".."){
                include $sharesPath.$file."/shareinfo.php";
                //echo $sharesPath.$file."</br>";
                if(!is_file($sharesPath.$file."/shareinfo.php")){
                    $brokenShareCount++;
                    $brokenShareList .= '<form method="POST" action="" style="margin-bottom: 0px;">Folder Name (ID): '.$file.'</form>';
                }else if($shareCreatorName == $user){
                    $mySharesCounter++;
                    $mySharesList .= '<form method="POST" action="" style="margin-bottom: 0px;">'.$shareName.'<span style="padding-left:20px"></span> (owner: '.$shareCreatorName.')<span style="padding-left:20px"></span><a href="index.php?share='.$shareID.'/">browse share</a><span style="padding-left:10px"></span><input name="mount_share" value="mount" type="submit" disabled><input name="edit_share" value="edit" type="submit"><input name="delete_share" value="delete" type="submit"><span style="padding-left:20px"></span> (shareID: '.$shareID.')<input name="shareToEdit" value="'.$shareID.'" type="hidden"></form>';
                }else{
                    $otherSharesCounter++;
                    $otherSharesList .= '<form method="POST" action="" style="margin-bottom: 0px;">'.$shareName.'<span style="padding-left:20px"></span> (owner: '.$shareCreatorName.')<span style="padding-left:20px"></span><a href="index.php?share='.$shareID.'/">browse share</a><span style="padding-left:10px"></span><input name="mount_share" value="mount" type="submit" disabled><span style="padding-left:20px"></span> (shareID: '.$shareID.')<input name="selectedShareToEdit" value="'.$shareID.'" type="hidden"></form>';
                    
                }
                //echo $shareName.'<span style="padding-left:20px"></span> (owner: '.$shareCreatorName.')<span style="padding-left:20px"></span><a href="index.php?share='.$shareID.'/">browse share</a><span style="padding-left:10px"></span><input name="mount_share" value="mount" type="submit"><span style="padding-left:20px"></span> (shareID: '.$shareID.')</br>';
            }
        }
        if( $mySharesCounter > 0 || $userMaximumShares > 0){
            //print my shares
            echo "</br>my shares (".$mySharesCounter." / ".$userMaximumShares.') <span style="padding-left:20px"></span><form method="POST" action="" style="margin-bottom: 0px; display:inline;"><input name="share_createNew" value="create new share" type="submit"></form></br>';
            if($mySharesCounter == 0){
                echo '&nbsp&nbsp&nbsp&nbsp you haven\'t created any shares';
            }
            echo $mySharesList;
            //print other shares
            echo "</br></br>other shares (".$otherSharesCounter.")</br>";
            if($otherSharesCounter == 0){
                echo '&nbsp&nbsp&nbsp&nbsp no shares have been shared with you';
            }
            echo $otherSharesList;
        }else{
            echo "</br>shares (".$otherSharesCounter.")</br>";
            if($otherSharesCounter == 0){
                echo '&nbsp&nbsp&nbsp&nbsp no shares have been shared with you';
            }
            echo $otherSharesList;
        }
        if ($brokenShareCount > 0){
            echo "</br></br></br>broken shares (".$brokenShareCount.") please contact your administrator to fix this problem</br>";
            echo $brokenShareList;
        }
    }
    
    //create new share
    function createNewShareFunction($datarootpath, $user, $shareName){
        $newShareID = md5(uniqid("word", true));
        $sharePath = $datarootpath.'/users/'.$user.'/shares/'.$newShareID.'';
        //echo $sharePath.'/data/';
        mkdir($sharePath.'/');
        mkdir($sharePath.'/data/');
        mkdir($sharePath.'/users/');
        
        $shareFileData = '<?php $shareCreatorName="'.$user.'";$shareName="'.$shareName.'";$shareID="'.$newShareID.'";?>';
        $shareFileInfo = fopen($sharePath.'/shareinfo.php'.'',"w");
        fwrite($shareFileInfo, $shareFileData);
        fclose($shareFileInfo);
        return $newShareID;


    }
function editShareInterface($datarootpath, $user, $shareID){
    $sharesPath = $datarootpath.'/users/'.$user.'/shares/';
    //echo $sharesPath.$shareID."/shareinfo.php";
    include $sharesPath.$shareID."/shareinfo.php";
    echo "</br>owner: ".$shareCreatorName;
    echo "</br>shareID: ".$shareID;
    include $sharesPath.$shareID."/users/".$user."/userPermissions.php";
    //check if the user has permission to edit the share
    if(($shareCanAddUsers=='true')||($shareCreatorName == $user) ){
        
        $usersInShare = getUsersOfShare($datarootpath, $user, $shareID);
        echo "</br>sharename: ".$shareName.'</br></br></br>';
        echo '<form method="POST" action="" style="margin-bottom: 0px; display:inline;"><input name="shareToEdit" value="'.$shareID.'" type="hidden">'."users in share (".sizeof($usersInShare)."): ".'<input name="add_user_to_share" value="add users to share" type="submit"></form>';
        foreach($usersInShare as $userInShare){
            include $sharesPath.$shareID."/users/".$userInShare."/userPermissions.php";
            echo '</br></br><form method="POST" action="" style="margin-bottom: 0px;"><input name="shareToEdit" value="'.$shareID.'" type="hidden"><input name="shareUser" value="'.$userInShare.'" type="hidden">'.$userInShare;
            echo '<span style="padding-left:20px"></span><input name="edit_user_in_share" value="edit" type="submit"><input name="remove_user_in_share" value="remove from share" type="submit"></form>';
            echo 'Permissions:  can download = '.$shareCanDownload.';  can upload = '.$shareCanUpload.';  can delete/edit = '.$shareCanDelete.';  can add users = '.$shareCanAddUsers.';';
        }
        echo '</br></br><form method="POST" action="" style="margin-bottom: 0px;"><input name="shareToEdit" value="'.$shareID.'" type="hidden"></br></br><input name="ok" value="ok" type="submit"><span style="padding-left:50px"></span><input name="delete_share" value="delete share" type="submit"></form>';
    }else{
        ecad_php_log($datarootpath,"WARNING","edit shre interface request without permissions ".'[ID:'.$shareID.']');
        echo "you have no permissions to edit this share";
    }
}
function getUsersOfShare($datarootpath, $user, $shareID){

    $sharesUserPath = $datarootpath.'/users/'.$user.'/shares/'.$shareID.'/users/';
    $files = scandir($sharesUserPath);
    $userInShare = array();
    //echo "shares:</br>";
    foreach($files as $file){
        if($file != "." && $file != ".."){
            array_push($userInShare, $file);
            
        }
    }
    return $userInShare;
}

    //add user to share (disalow "/" character! befor running)

function addUserToShareSubmit($datarootpath, $user, $shareID, $toAddUser, $shareCanUpload, $shareCanDelete, $shareCanDownload, $shareCanAddUsers){
    if( $toAddUser != $user){
        //add to allowed list of share
        $sharePath = $datarootpath.'/users/'.$user.'/shares/'.$shareID.'';
        include $sharePath.'/shareinfo.php';

        mkdir($sharePath.'/users/'.$toAddUser.'/');
        
        $shareFileData = '<?php $shareCanUpload="'.$shareCanUpload.'";$shareCanDelete="'.$shareCanDelete.'";$shareCanDownload="'.$shareCanDownload.'";$shareCanAddUsers="'.$shareCanAddUsers.'";?>';
        $shareFileInfo = fopen($sharePath.'/users/'.$toAddUser.'/userPermissions.php'.'',"w");
        fwrite($shareFileInfo, $shareFileData);
        fclose($shareFileInfo);
        
        //add share to other user
        $otherUserPath = $datarootpath.'/users/'.$toAddUser.'/shares/'.$shareID.'';
        mkdir($otherUserPath.'/');
        
        $shareFileData = '<?php $shareCreatorName="'.$shareCreatorName.'";$shareName="'.$shareName.'";$shareID="'.$shareID.'";?>';
        $shareFileInfo = fopen($otherUserPath.'/shareinfo.php'.'',"w");
        fwrite($shareFileInfo, $shareFileData);
        fclose($shareFileInfo);
    }else{
        echo "you are allready owner of this share";
    }

}
function getSafeShareID(){
    $shareID = $_GET["share"];
    //remove escape characters
    //validate path
    if (strlen($shareID) >0){
        if ($shareID[0] != "/"){
            $shareID ="/".$shareID;
        }
    }else{
        $shareID = "/none/";
    }
    $shareID = $shareID."/";
    $shareID = explode("/", $shareID)[1];
    $shareID = str_replace ("." , "" , $shareID);
    
    //new--
    /*
    $fullpath = str_replace ("\\.." , "" , $fullpath);
    $fullpath = str_replace ("..\\" , "" , $fullpath);
    $fullpath = str_replace ("/.." , "" , $fullpath);
    $fullpath = str_replace ("../" , "" , $fullpath);
     */
    //-----

    return $shareID;
}
function getSafeSharePath(){
    $sharePath = $_GET["share"];
    //remove escape characters
    //validate path
    if (strlen($sharePath) >0){
        if ($sharePath[0] != "/"){
            $sharePath ="/".$sharePath;
        }
    }else{
        $sharePath = "/none/";
    }
    $shareID = explode("/", $sharePath)[1];
    $shareID = str_replace ("." , "" , $shareID);

    $sharePath =  substr($sharePath, strlen($shareID)+1);
    
    $sharePath = str_replace ("\\.." , "" , $sharePath);
    $sharePath = str_replace ("..\\" , "" , $sharePath);
    $sharePath = str_replace ("/.." , "" , $sharePath);
    $sharePath = str_replace ("../" , "" , $sharePath);

    
    return $sharePath;
    
}
function getSafeFullSharePath($datarootpath, $shareCreatorName, $shareID, $sharePath){
    $shareFullPath = $datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/data/'.$sharePath;
    return $shareFullPath;

}
function printUserInterfaceShareFileViewer($shareID, $user, $path, $fullpath, $datarootpath, $can_delete, $can_upload, $nichtgelisteteDatein, $ecad_php_version, $shareName, $shareID){
    
    //prints head of user interface
    printUserHeader($user, $ecad_php_version);
    
    //print share name and id
    echo 'share: '.$shareName.'&nbsp&nbsp&nbsp&nbsp( ID: '.$shareID.' )</br>';
    
    //prints new path display system
    printUserPathForShare($shareID.$path);
    
    if(file_exists($fullpath.'/')){
        //logs the file request
        ecad_php_log($datarootpath,"INFO","shared folder request ".'['.$shareID.$path.']');
        
        //get files and sort
        $files = scandir($fullpath.'/');
        sort($files, SORT_NATURAL);
        //counts howmany files are found
        $datein = 0;
        
        //prints form header and buttons
        printFileEditUploadDeleteCreateButtons($can_delete, $can_upload);
        
        //prints upload form
        printUserFileUploadScript();
        
        //check if there is a file in the folder that is not listed on not listed
        if(count(array_diff($files, $nichtgelisteteDatein)) > 1){
            //make select all files checkbox
            ?>
            <input type="checkbox" name="action_toggleCheckboxSelection" value="true" onClick="toggle_file_checkboxes(this)"></input> </br>
            
            <script language="JavaScript">
            function toggle_file_checkboxes(source) {
                //checkboxes = document.getElementsByID('id_file_checkbox');
                checkboxes = document.getElementsByClassName('id_file_checkbox');
                for(var i=0, n=checkboxes.length;i<n;i++) {
                    checkboxes[i].checked = source.checked;
                }
            }
            </script>
            
            <?php
        }
        
        foreach($files as $file){
            //print files
            //$datein++;
            //include $fullpath.'/';
            //if(include )
            $datein = $datein + printFileAndInfoForShare($file , $nichtgelisteteDatein, $shareID.$path, $can_delete, $fullpath);
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

function printFileAndInfoForShare($file , $nichtgelisteteDatein, $path, $can_delete, $fullpath){
    if (in_array ( $file , $nichtgelisteteDatein )){
        return 0;
        //files that are not listed for users
    }else{
        echo "\r\n";
        $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
        //makes a checkbox if user can delete files
        if($can_delete){ echo '<input type="checkbox" name='."'".'file_'.$file_in_html.''."'".' value="true" class="id_file_checkbox"></input> ';}
        
        //writes the filesize of the given file in human readeble form
        printFileSize($fullpath, $file);
        
        //path system for shown files and folders
        $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?share=".$path;
        
        echo '<a href="'.$newpath.$file.'/">'.$file."       ".'</a> </br>';
    }
    return 1;
}
function printUserPathForShare($path){
    if ($path == '/'){
        echo "path: ";
        echo'<a href="'.'">root</a><a> /</a>';
    }else{
        $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?share=".split('/',$path)[0].'/';
        
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
function createShareView(){
    echo '<form method="POST" action="" style="margin-bottom: 0px;">';
    echo '</br>share name: <input type="text" name="share_name" value=""></input></br>';
    
    echo '</br>users to share with:</br><textarea rows="10" cols="30" name="usersInShare"></textarea></br>';
    echo 'can download: <input type="checkbox" name="can_download" value="can_delete" checked></br>';
    echo 'can upload: <input type="checkbox" name="can_upload" value="can_upload"></br>';
    echo 'can edit / delete: <input type="checkbox" name="can_delete" value="can_delete"></br>';
    echo 'can add users: <input type="checkbox" name="can_addUsers" value="can_delete"></br>';
    echo '<input name="createNewShareSubmit" value="create share" type="submit">';
    echo '</form>';
}

function createNewShareSubmit($datarootpath, $user, $shareName, $usersToAdd, $shareCanUpload, $shareCanDelete, $shareCanDownload, $shareCanAddUsers){
    //create share
    $shareID = createNewShareFunction($datarootpath, $user, $shareName);
    //get list of users to add to share
    $usersToAdd = explode("\r\n", $usersToAdd);
    //create users
    foreach($usersToAdd as $userToAdd){
        $toAddUser = getSafeString($userToAdd);
        if ($toAddUser != ""){
            //check if user exists
            if(is_dir($datarootpath.'/users/'.$toAddUser)){
                //add user to share
                addUserToShareSubmit($datarootpath, $user, $shareID, $toAddUser, $shareCanUpload, $shareCanDelete, $shareCanDownload, $shareCanAddUsers);
            }else{
               echo 'can\'t add the user: '.$toAddUser.' (user doesnt exist)</br>';
            }
        }
    }
}

function addUserToShareView($shareID){
    echo '<form method="POST" action="" style="margin-bottom: 0px;">';
    echo '<input name="shareToEdit" value="'.$shareID.'" type="hidden">';
    
    echo '</br>users to add to share:</br><textarea rows="10" cols="30" name="usersInShare"></textarea></br>';
    echo 'can download: <input type="checkbox" name="can_download" value="can_delete" checked></br>';
    echo 'can upload: <input type="checkbox" name="can_upload" value="can_upload"></br>';
    echo 'can edit / delete: <input type="checkbox" name="can_delete" value="can_delete"></br>';
    echo 'can add users: <input type="checkbox" name="can_addUsers" value="can_delete"></br></br>';
    echo '<input name="addUsersToShareSubmit" value="add users to share" type="submit">';
    echo '</form>';
}
function addUsersToShareSubmit($datarootpath, $user){
    $shareID = getSafeString($_POST['shareToEdit']);
    //get share information
    include $datarootpath.'/users/'.$user.'/shares/'.$shareID.'/shareinfo.php';
    //get user permissions
    include$datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/users/'.$user.'userPermissions.php';
    if(($shareCanAddUsers == 'true')||($user == $shareCreatorName) ){
        $usersToAdd = $_POST['usersInShare'];
        $shareCanUpload = $_POST[('can_upload')]  ? "true" : "false";
        $shareCanDelete = $_POST[('can_delete')]  ? "true" : "false";
        $shareCanDownload = $_POST[('can_download')]  ? "true" : "false";
        $shareCanAddUsers = $_POST[('can_addUsers')]  ? "true" : "false";
        $usersToAdd = explode("\r\n", $usersToAdd);
        //create users
        foreach($usersToAdd as $userToAdd){
            $toAddUser = getSafeString($userToAdd);
            if ($toAddUser != ""){
                
                //echo ";added user:".$toAddUser.";".$userToAdd."-";
                if(is_dir($datarootpath.'/users/'.$toAddUser)){
                    addUserToShareSubmit($datarootpath, $shareCreatorName, $shareID, $toAddUser, $shareCanUpload, $shareCanDelete, $shareCanDownload, $shareCanAddUsers);
                }else{
                    echo 'can\'t add the user: '.$toAddUser.' (user doesnt exist)</br>';
                }
            }
        }
    }else{
        ecad_php_log($datarootpath,"WARNING","add user to share without permissions ".'[ID:'.$shareID.']');
        echo 'you don\'t have permissions to do that!!!';
    }
    
    

    
}
function editUserInShareView($datarootpath, $user){
    $shareID = getSafeString($_POST['shareToEdit']);
    $shareUser = getSafeString($_POST['shareUser']);
    //get share information
    include $datarootpath.'/users/'.$user.'/shares/'.$shareID.'/shareinfo.php';
    //get user permissions
    include $datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/users/'.$shareUser.'/userPermissions.php';
    
    echo '<form method="POST" action="" style="margin-bottom: 0px;">';
    echo '<input name="shareToEdit" value="'.$shareID.'" type="hidden">';
    
    echo '</br>users to edit:</br> <input type="text" name="shareUser" value="'.$shareUser.'" readonly></br>';
    if($shareCanDownload == 'true'){
        echo 'can download: <input type="checkbox" name="can_download" value="can_delete" checked></br>';
    }else{
        echo 'can download: <input type="checkbox" name="can_download" value="can_delete"></br>';
    }
    if($shareCanUpload == 'true'){
        echo 'can upload: <input type="checkbox" name="can_upload" value="can_upload" checked></br>';
    }else{
        echo 'can upload: <input type="checkbox" name="can_upload" value="can_upload"></br>';
    }
    if($shareCanDelete == 'true'){
        echo 'can edit / delete: <input type="checkbox" name="can_delete" value="can_delete" checked></br>';
    }else{
        echo 'can edit / delete: <input type="checkbox" name="can_delete" value="can_delete"></br>';
    }
    if($shareCanAddUsers == 'true'){
        echo 'can add users: <input type="checkbox" name="can_addUsers" value="can_delete" checked></br>';
    }else{
        echo 'can add users: <input type="checkbox" name="can_addUsers" value="can_delete"></br>';
    }

    echo '<input name="editUserInShareSubmit" value="submit changes" type="submit"><input name="edit_share" value="abort" type="submit">';
    echo '</form>';
}
function editUserInShareSubmit($datarootpath, $user){
    $shareID = getSafeString($_POST['shareToEdit']);
    $toAddUser = getSafeString($_POST['shareUser']);
    $shareCanUpload = $_POST[('can_upload')]  ? "true" : "false";
    $shareCanDelete = $_POST[('can_delete')]  ? "true" : "false";
    $shareCanDownload = $_POST[('can_download')]  ? "true" : "false";
    $shareCanAddUsers = $_POST[('can_addUsers')]  ? "true" : "false";
    
    $sharePath = $datarootpath.'/users/'.$user.'/shares/'.$shareID.'';
    //get share info
    include $sharePath.'/shareinfo.php';
    //get user permisions
    includesharePath.'/users/'.$user.'/userPermissions.php';
    if(($shareCanAddUsers=="true")||($user == $shareCreatorName) ){
        if( $toAddUser != $user){
            //add or edit user in share
            
            //add user to share
            mkdir($sharePath.'/users/'.$toAddUser.'/');
            
            $shareFileData = '<?php $shareCanUpload="'.$shareCanUpload.'";$shareCanDelete="'.$shareCanDelete.'";$shareCanDownload="'.$shareCanDownload.'";$shareCanAddUsers="'.$shareCanAddUsers.'";?>';
            $shareFileInfo = fopen($sharePath.'/users/'.$toAddUser.'/userPermissions.php'.'',"w");
            fwrite($shareFileInfo, $shareFileData);
            fclose($shareFileInfo);
            
            //add share to other user
            $otherUserPath = $datarootpath.'/users/'.$toAddUser.'/shares/'.$shareID.'';
            mkdir($otherUserPath.'/');
            
            $shareFileData = '<?php $shareCreatorName="'.$shareCreatorName.'";$shareName="'.$shareName.'";$shareID="'.$shareID.'";?>';
            $shareFileInfo = fopen($otherUserPath.'/shareinfo.php'.'',"w");
            fwrite($shareFileInfo, $shareFileData);
            fclose($shareFileInfo);
        }else{
            echo "you can't add yourself to the share!!!";
        }
    }else{
        ecad_php_log($datarootpath,"WARNING“,“edit user in share without permissions ".'[ID:'.$shareID.']');
        echo 'you dont have permissions  to do that!!!!';
    }
}

function removeUserFromShareView($datarootpath, $user){
    $shareID = getSafeString($_POST['shareToEdit']);
    $shareUser = getSafeString($_POST['shareUser']);
    
    //get information about share
    include $datarootpath.'/users/'.$user.'/shares/'.$shareID.'/shareinfo.php';
    //get the permissions of the user
    include $datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/users/'.$user.'/userPermissions.php';
    //print interface
    echo '<form method="POST" action="" style="margin-bottom: 0px;">';
    echo '<input name="shareToEdit" value="'.$shareID.'" type="hidden">';
    echo '<input name="shareUser" value="'.$shareUser.'" type="hidden">';
    echo '</br>do you really want to remove the user: '.$shareUser.'?</br>from the share: '.$shareName.'</br>(ID: '.$shareID.')</br></br>';
    echo '<input name="remove_user_in_share_Submit" value="remove user from share" type="submit"><input name="edit_share" value="abort" type="submit">';
    echo '</form>';
    
    
}
function removeUserFromShareSubmit($datarootpath, $user){
    $shareID = getSafeString($_POST['shareToEdit']);
    $shareUser = getSafeString($_POST['shareUser']);
    
    //get information about share
    include $datarootpath.'/users/'.$user.'/shares/'.$shareID.'/shareinfo.php';
    //get the permissions of the user
    include $datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/users/'.$user.'/userPermissions.php';
    
    if($shareCanAddUsers == 'true' || $user == $shareCreatorName){
        if($shareUser != $shareCreatorName){
            //remove user from share
            removeUserFromShareFunction($datarootpath, $user, $shareCreatorName, $shareID, $shareUser);
        }else{
            echo 'you cant remove the owner of the share!!</br>';
        }
    }else{
        ecad_php_log($datarootpath,"WARNING","remove user from share without permissions ".'[ID:'.$shareID.']');
        echo 'you don\'t have permisions to remove users!!</br>';
    }

}

function removeUserFromShareFunction($datarootpath, $user, $shareCreatorName, $shareID, $userToRemove){
    //remove user from share
    rrmdir($datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/users/'.$userToRemove.'/');
    //remove share from user
    rrmdir($datarootpath.'/users/'.$userToRemove.'/shares/'.$shareID.'/');
}

function deleteShareView($datarootpath, $user){
    $shareID = getSafeString($_POST['shareToEdit']);
    
    include $datarootpath.'/users/'.$user.'/shares/'.$shareID.'/shareinfo.php';
    
    echo '<form method="POST" action="" style="margin-bottom: 0px;">';
    echo '<input name="shareToEdit" value="'.$shareID.'" type="hidden">';
    echo '</br>Do you really want to delete the share: '.$shareName.'</br>(ID: '.$shareID.')</br></br>';
    echo '<input name="deleteShareSubmit" value="really delete share" type="submit">  <input name="edit_share" value="abort" type="submit">';
    echo '</form>';
    
}

function deleteShareSubmit($datarootpath, $user){
    //get selected share
    $shareID = getSafeString($_POST['shareToEdit']);
    //get innformation about share
    include $datarootpath.'/users/'.$user.'/shares/'.$shareID.'/shareinfo.php';
    //get user permissions
    include $datarootpath.'/users/'.$shareCreatorName.'/shares/'.$shareID.'/users/'.$user.'/userPermissions.php';
    
    //check if user has permissions or is owner
    if($shareCreatorName == $user){
        //remove all user from share
        $files = scandir($datarootpath.'/users/'.$user.'/shares/'.$shareID.'/users/');
        foreach($files as $file){
            if($file != "." && $file != ".."){
                //remove a user
                removeUserFromShareFunction($datarootpath, $user, $shareCreatorName, $shareID, $file);
            }
        }
        //delete share folder
        rrmdir($datarootpath.'/users/'.$user.'/shares/'.$shareID);
        
        echo 'share deleted!</br>';
    }else{
        ecad_php_log($datarootpath,"WARNING","delete share without permissions ".'[ID:'.$shareID.']');
        echo 'the share can only be delted by the owner!!!';
    }
}

function countMyShares($datarootpath, $user){
    $sharesPath = $datarootpath.'/users/'.$user.'/shares/';
    $files = scandir($sharesPath);
    $mySharesCounter = 0;
    
    foreach($files as $file){
        if($file != "." && $file != ".."){
            include $sharesPath.$file."/shareinfo.php";
            //echo $sharesPath.$file."</br>";
            if($shareCreatorName == $user){
                $mySharesCounter++;
            }else{
            }
        }
    }
    return $mySharesCounter;
}


?><?php
//user panel----------------------------------------------------------------------------------------------------------------------------------------------------
function showUserPanel($datarootpath, $user, $ecad_php_version){
    echo $ecad_php_version." &nbsp&nbsp&nbsp    user: ".$user.' <span style="padding-left:30px"></span> <a href="index.php?action=logout">  logout </a></br>';
    echo '</br><span style="padding-left:20px"></span><a href="index.php?path=" >my files</a></br>';
    echo '</br><span style="padding-left:20px"></span><a href="index.php?share">shares</a></br>';
    echo '</br><span style="padding-left:20px"></span><a href="index.php?page=/">my pages</a> (not implemented)</br>';
    echo '</br><span style="padding-left:20px"></span><a href="index.php?myfavorites">my favorites</a> (not implemented)</br>';
    echo '</br><span style="padding-left:20px"></span><a href="index.php?usersettings">user settings</a> (not implemented)</br>';
}

?>