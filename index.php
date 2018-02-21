<?php


    //change in the folowing only in the config.php file by copying them there and changing the values, or else you lose your configuration when you update!!!
    
    $debug = false;
    $ecad_php_version ="ECAD PHP file hub v0.2.04n_d";
    $ecad_php_version_number = "v0.2.04n_d";
    $ecad_php_version_id = 135;
    
    //install if not installed
    installifneeded($ecad_php_version_number,$ecad_php_version_id);
    

    
    $show_ecad_php_version_on_title = true;
    
    $allowAllCharactersInObjectNames= false;
    //if set to true will allow all characters except except for certain. if left on false will only allow the folowing: a-z0-9.-
    
    $maximalUploadSize = "50M";
    //if changed needs also to be set in the .htaccess file!!
    //sample: php_value upload_max_filesize 50M and php_value post_max_size 50M
    //you need to enable AllowOverwritte all in httpd.conf or apache2.conf
    
    $showAdministratorPath = false;
    $userIsAdmin = false;
    $nichtgelisteteDatein = array("index.php", ".htaccess", ".", "..");
    $showFileViewer = false;
    
    //variables for compatiblety
    $canAccessSystemFolder = false;
    $log_fileUpload = true;
    
    //check if the upload size was too big
    if( strpos(error_get_last()["message"], "exceeds the limit of") !== false){
        echo "!ERROR! The upload exceeded the maximum upload size of the system! (maximum: ".ini_get("post_max_size").")</br>";
    }

    //load config
    include "config.php";
    
    
    //execute scripted update
    if(file_exists('update_parameters.php')){
        include 'update_parameters.php';
        execute_update_parameter($datarootpath);
        ecad_php_log($datarootpath,"INFO","update parameters have been executed ");
        unlink('update_parameters.php');
    }

    

    
    
    //1-ignores errors 2-shows errors
    error_reporting(1);
    
    //authentification service cockie
    $authentificated = false;
    
    //create test share:
    //createNewShareFunction($datarootpath, "admin", "debug share");
    //addUserToShareSubmit($datarootpath, "admin", "share id", "user0", "false", "false", "false", "false");
    
    
    //user authentification via cookie:
    //check if login cockie exists on host
    if ($_COOKIE['ECAD_PHP_fileviewer_login']) {
        list($c_username,$cookie_hash) = explode(',',$_COOKIE['ECAD_PHP_fileviewer_login']);
        //clean input
        $c_username = getSafeString($c_username);
        $cookie_hash = getSafeString($cookie_hash);

        
        //verify if user exists and check cookie length
        if(file_exists($datarootpath."/users/".$c_username ) && strlen($cookie_hash) >3){
            //get user settings
            include $datarootpath."/users/".$c_username."/userconfig.php";
            //check if cookie exists
            if(file_exists($datarootpath."/users/".$c_username.'/sessions/'.$cookie_hash)){
                
                
                //set user to logged in
                $user = $c_username;
                $userpath="/".$user;
                $authentificated = true;
                
                
                //log last seen
                //get IP
                $client_Address = $_SERVER['REMOTE_ADDR'];
                //get time
                $current_time = date("Y.m.d-H.i.s",time());
                //write
                file_put_contents($datarootpath."/users/".$c_username.'/sessions/'.$cookie_hash.'/last_seen.txt', $current_time.';'.$client_Address);
            }else{
                //authentification of cookie failed
                $authentificated = false;
                ecad_php_log($datarootpath,"WARNING","no valid cockie: ".$_COOKIE['ECAD_PHP_fileviewer_login']);
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
        setcookie('ECAD_PHP_fileviewer_login',"",-1);

        echo 'please wait. completing logout......</br>';
        $authentificated = false;
        header("Refresh:0; url=index.php");
        exit();
    }

    //-------------------
if ($authentificated) {
    if($user == "admin"){
        $userIsAdmin = true;
        //update script
        if(file_exists('update.php')){
            include 'update.php';
            ecad_php_log($datarootpath,"INFO","update script hase been executed ");
        }
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
            //check for updates
            echo makeCheckForUpdateNotification();
            
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
                $username = $_POST['username'];
                create_user($username,$datarootpath);
                edit_user_submit($datarootpath);
                
            }
        }
        //edits a user
        if ( isset( $_POST['edit_user_submit'] ) ) {
            edit_user_submit($datarootpath);
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
                            //upload multiple files if file is being uploaded
                            if ( isset( $_POST['upload_multiple_file'] ) && (($shareCanUpload == 'true')||($user == $shareCreatorName)) ) {
                                upload_multiple_file($datarootpath, $log_fileUpload, $fullSharePath, $sharepath, $maximalUploadSize, $maximalUploadSize);
                            }
                            
                            //download multiple files as zip archive for shares
                            if ( isset( $_POST['download_multiple'] ) && (($shareCanDownload == 'true')||($user == $shareCreatorName)) ){
                                //$shareCanDownload
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
            
        }else if (isset($_GET["settings"])){
            //show user panel
            echo '<a href="index.php?userpanel"><-- back to user panel</a></br>';
            //TODO
            
            //Change password (if allowed)
            //TODO
            
            //change email (if allowed)
            //TODO
            
            
            //close sessions
            if(isset($_POST['close_session_submit_name'])) closeSessionFromSettings();
            
            //show active sessions
            listActiveSessions();

            
            
            //close all session except this one
            //TODO
            
            
            
    
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
            //download file if path is a file
            if(is_file($fullpath)){
                
                
                ecad_php_log($datarootpath,"INFO","file download ".'['.$path.']');
                //check if editing
                if(isset($_GET["view"])){
                    $ext = pathinfo($fullpath, PATHINFO_EXTENSION);
                    switch($ext)
                    {
                        case "bmp":
                        case "jpg":
                        case "gif":
                        case "png":
                        case "svg":
                        case "jpeg":
                        case "pdf": makeDownload($fullpath, $path, true); break;
                            
                        case "txt": echo "can't view file an the moment (not implemented)"; break;
                            
                            //for all other files make normal download
                        default: makeDownload($fullpath, $path, false); break;
                    }
                }else{
                    makeDownload($fullpath, $path, false);
                }
                
                

                
                
                
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
                //upload multiple files if file is being uploaded
                if ( isset( $_POST['upload_multiple_file'] ) && $can_upload ) {
                    echo "uploading multiple files: ";
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
    //no valid cockie found (user is currently not logged in)
    //login system--------------------------------------------------
    //if normal login
    //check if quick login check
    if($allow_quick_login && isset($_GET['cfqlc'])){
        echo quickKeyCheckForLogin(getSafeString($_COOKIE['ECAD_PHP_fileviewer_quickkey']));
        
    }else if($allow_quick_login && isset($_GET['gnqk'])){
        removeQuickLoginKeyFromServer(getSafeString($_COOKIE['ECAD_PHP_fileviewer_quickkey']));
        echo getCurrentQuickLoginKey();
        

        
        
        
    }else if($_POST['submit_login']){
        $user = getSafeFileName($_POST['user']);
        $pass = $_POST['pass'];
        
        $loginaccepted = false;
        if(isset($_POST['user'])){
            if(file_exists($datarootpath."/users/".$user)){
                include $datarootpath."/users/".$user."/userconfig.php";
                if(password_verify($pass , $userpasswordHash ))
                    $loginaccepted = true;
            }
        }
        
        if($loginaccepted && $_POST['user'] != null)
        {
            //remove quick key from server
            removeQuickLoginKeyFromServer(getSafeString($_COOKIE['ECAD_PHP_fileviewer_quickkey']));
            //remove quick key from client
            setcookie('ECAD_PHP_fileviewer_quickkey','',-1);


            //handel when login sucessfull
            handelLoginAccepted($datarootpath, $user, $pass, $secret_word, 'normal login');
        }
        else
        {
            //handel if not logged in and no valid login
            handelLoginScreenView($show_ecad_php_version_on_title, $ecad_php_version, $datarootpath);
        }
        
        
    }else{
        //check quick login attempt
        if(($allow_quick_login && $show_quick_login && quickKeyCheckForLoginOrDelete())||($allow_quick_login && isset($_GET['quick']) && quickKeyCheckForLoginOrDelete())){
            //logged in
            
            
            //check for quick login window
        }elseif($allow_quick_login && isset($_GET['quick'])){
            printQuickLoginWindow();
            

        }else  if(isset($_GET['share']) && $allow_public_shares){
            //public share
            //TODO
            
        }else if(isset($_GET['resetPassword']) && $allow_password_reset_functionality){
            //request link to reset password
            //TODO
            
        }else if(isset($_GET['newPassword']) && $allow_password_reset_functionality){
            //reset password with provided link
            //TODO
            
            
        }else{
            handelLoginScreenView($show_ecad_php_version_on_title, $ecad_php_version, $datarootpath);
        }
        
    }
}
    
    
    
    
    
    
    
    
    
    //unsorted functions-------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    function getSafeString($str){
        global $allowAllCharactersInObjectNames;
        
        if($allowAllCharactersInObjectNames){
            $str = strip_tags($str);
            $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
            $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
            $str = strtolower($str);
            $str = html_entity_decode( $str, ENT_QUOTES, "utf-8" );
            $str = htmlentities($str, ENT_QUOTES, "utf-8");
            $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
        }else{
            $str = preg_replace('/[^a-z0-9\.\-]/i', '_', $str);
        }
        
        return $str;

    }
    function getSafeFileName($str){
        return getSafeString($str);
    }
    
    //delete folder and sub folder
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



//system functions --------------------------------------------------------------------------------------------------------------------------------------------------------------------
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

function makeDownloadHead($file, $type, $filename,$isAviewableFile) {
    
    
    header("Content-Type: ".$type);
    
    //header("Content-Disposition: attachment; filename=\"$file\"");
    if(!$isAviewableFile) header("Content-Disposition: attachment; filename=\"$filename\"");
    //Give client the file size
    header("Content-length: ".filesize($file));

}
?><?php
function installifneeded($ecad_php_version_number,$ecad_php_version_id) {
        //$secret_word = "word";
    if(!file_exists("config.php")){
        $dataFolderName = '/ECAD PHP file hub data';
        //ecad php config file
        $ecadphpconfigfile = fopen("config.php", "w");
        $ecadphpconfigStandard = '<?php'."\r\n".
        '$datarootpath='."'".__DIR__.$dataFolderName."'".';'."\r\n".
        '$firstInstallationVersion='."'".$ecad_php_version_number."'".';'."\r\n".
        '$firstInstallationID='."'".$ecad_php_version_id."'".';'."\r\n".
        '$log_fileUpload=true;'."\r\n".
        '$allowAllCharactersInObjectNames=false;'."\r\n".
        '$show_password_reset_button=false;'."\r\n".
        '$allow_password_reset_functionality=false;'."\r\n".
        '$allow_public_shares=false;'."\r\n".
        
        '$automatically_check_for_updates=true;'."\r\n".
        '$update_notification=true;'."\r\n".
        '$auto_update=false;'."\r\n".
        
        '$lastUpdateCheck='."'0'".';'."\r\n".
        '$UpdateRecheckTimer='."'86400'".';'."\r\n".
        '$updateAvailable=false;'."\r\n".
        
        '$allow_quick_login=false;'."\r\n".
        '$show_quick_login=false;'."\r\n".
        '$quick_login_timeout='."'60'".';'."\r\n".
        '$set_system_timeout_overwrite=false;'."\r\n".
        '$set_password_requirements='."'".'^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$'."'".';'." //requirements length:8 , minimum of at least 1 upper case, lower case and a number \r\n".
        '?>';



fwrite($ecadphpconfigfile, $ecadphpconfigStandard);
fclose($ecadphpconfigfile);
//ecad php data folder
mkdir('.'.$dataFolderName.'/shares', 0777, true);
mkdir('.'.$dataFolderName.'/pages', 0777, true);
mkdir('.'.$dataFolderName.'/users', 0777, true);
mkdir('.'.$dataFolderName.'/quicklogin', 0777, true);




//create user0
//create_user("user0",'.'.$dataFolderName.'/');
//configure user
//edit_user('.'.$dataFolderName, "user0", "admin", "false", "", "false", "false", "", "false", "false", "0","false","false","false");
//create user0 test folder
//mkdir('.'.$dataFolderName.'/users/user0/data/test', 0777, true);


//create admin
create_user("admin",'.'.$dataFolderName.'/');
//configure user
edit_user('.'.$dataFolderName, "admin", "admin", "false", "", "false", "false", "", "false", "false", "0","false","false","false");
//make changes for admin
file_put_contents('.'.$dataFolderName.'/users/admin/userconfig.php','<?php'."\r\n".'$userIsAdmin= true;'."\r\n".'$canAccessSystemFolder= true;'.'?>',FILE_APPEND);


//configurate htaccess
$ecad_php_htaccess_file = fopen('.'.$dataFolderName.'/.htaccess', "w");
$ecad_php_htaccess_file_Standard = '<Directory ./>'."\r\n".'Order deny,Allow'."\r\n".'Deny from all'."\r\n".'</Directory>';
fwrite($ecad_php_htaccess_file, $ecad_php_htaccess_file_Standard);
fclose($ecad_php_htaccess_file);

//config htaccess in root folder for file upload limit
//TODO
//file_put_contents(".htaccess","\r\nphp_value upload_max_filesize 50M\r\nphp_value post_max_size 50M",FILE_APPEND);

ecad_php_log(__DIR__.''.$dataFolderName.'',"INFO","ECAD PHP fileviewer successfully installed");
    }

}
function makeCheckForUpdateNotification(){
    global $automatically_check_for_updates;
    
    
    if($automatically_check_for_updates){
        global $update_notification, $lastUpdateCheck, $UpdateRecheckTimer, $updateAvailable, $ecad_php_version_number,$ecad_php_version_id;
        //https://github.com/epic-crap-app-designer/ECAD-PHP-File-Hub/releases/download/v0.2.03g/ECAD.PHP.File.HUB.Version.0.2.03g.zip
        //https://github.com/epic-crap-app-designer/ECAD-PHP-File-Hub/releases/tag/v0.2.03g
        if(($lastUpdateCheck+$UpdateRecheckTimer)<time()){
            

            
            //test
            //$currentVersionFromGithub = file_get_contents('current_version.txt');
            
            
            //download current version file
            //https://raw.githubusercontent.com/epic-crap-app-designer/ECAD-PHP-File-Hub/master/current_version.txt
            
            $currentVersionFromGithub = file_get_contents('https://raw.githubusercontent.com/epic-crap-app-designer/ECAD-PHP-File-Hub/master/current_version.txt');
            

            
            if($currentVersionFromGithub === FALSE) return "Update Notification:</br>can't check for updates! (couldn't connect to github.com)</br></br>";
            
            $currentVersionFromGithubParts = explode(";", $currentVersionFromGithub);
            //check update file version compatibelty
            if($currentVersionFromGithubParts[0] == 1){
                if($currentVersionFromGithubParts[1] >$ecad_php_version_id){
                    //update is available
                    $updateAvailable = true;
                    $update_notification = $currentVersionFromGithub;
                }else{$updateAvailable = false;}
                
                //set config.php
                
                $lastUpdateCheck = time();
                updateConfigPHPFile();
            }else{
                //update file incompatible
                return 'Update Notification:</br>there may be an update, but the update system is not compatible, please download the newest update manualy</br></br>';
            }

        }
        if($updateAvailable){
            //recheck if update has been installed already
            $currentVersionFromGithubParts = explode(";", $update_notification);
            if($currentVersionFromGithubParts[1] <=$ecad_php_version_id){
                //update is available
                $updateAvailable = false;
                $update_notification = '';
                updateConfigPHPFile();
            }else{
                global $userIsAdmin;
                if($userIsAdmin && isset($_GET["autoupdatedownload"])){

                   $updatePHPFile = file_get_contents('https://github.com/epic-crap-app-designer/ECAD-PHP-File-Hub/releases/download/'.$currentVersionFromGithubParts[2].'/update.php');
                   
                   if($updatePHPFile === FALSE) return "There was an error, please try downloading the update manualy</br>";
                   
                   $updatePHPFileObject = fopen("update.php", "w");
   
                   
                   fwrite($updatePHPFileObject, $updatePHPFile);
                   fclose($updatePHPFileObject);
                   
                   
                   header("Refresh:0; url=index.php?path=");
                }else{
                   
                   return 'Update available!</br>current Version: <a style="color:blue">'.$ecad_php_version_number.'</a> new version: <a style="color:blue">'.$currentVersionFromGithubParts[2].'</a></br>Try <a href="index.php?autoupdatedownload">autoupdate</a> (you will have to login as administrator)</br>or download the newest version manualy <a href="https://github.com/epic-crap-app-designer/ECAD-PHP-File-Hub/releases/tag/'.$currentVersionFromGithubParts[2].'">here</a></br></br>';
                }
            }


        }
    }
}



function updateConfigPHPFile(){
    ecad_php_log($ECAD_PHP_fileviewer_X_data_folder,"INFO","updating config.php");
    global $datarootpath,$firstInstallationVersion,$firstInstallationID,$log_fileUpload,$allowAllCharactersInObjectNames,$show_password_reset_button,$allow_password_reset_functionality,$allow_public_shares,$automatically_check_for_updates,$update_notification,$lastUpdateCheck,$UpdateRecheckTimer,$show_quick_login,$allow_quick_login,$quick_login_timeout,$set_system_timeout_overwrite,$set_password_requirements,$updateAvailable;
    
    $ecadphpconfigfile = fopen("config.php", "w");
    $ecadphpconfigStandard = '<?php'."\r\n".
    '$datarootpath='."'".$datarootpath."'".';'."\r\n".
    '$firstInstallationVersion='."'".$firstInstallationVersion."'".';'."\r\n".
    '$firstInstallationID='."'".$firstInstallationID."'".';'."\r\n".
    '$log_fileUpload='."'".$log_fileUpload."';\r\n".
    '$allowAllCharactersInObjectNames='."'".$allowAllCharactersInObjectNames."';\r\n".
    '$show_password_reset_button='."'".$show_password_reset_button."';\r\n".
    '$allow_password_reset_functionality='."'".$allow_password_reset_functionality."';\r\n".
    '$allow_public_shares='."'".$allow_public_shares."';\r\n".
    
    '$automatically_check_for_updates='."'".$automatically_check_for_updates."';\r\n".
    '$update_notification='."'".$update_notification."';\r\n".
    '$auto_update='."'".$auto_update."';\r\n".
    
    '$UpdateRecheckTimer='."'".$UpdateRecheckTimer."';\r\n".
    '$lastUpdateCheck='."'".$lastUpdateCheck."';\r\n".
    '$updateAvailable='."'".$updateAvailable."';\r\n".
    
    '$allow_quick_login='."'".$allow_quick_login."';\r\n".
    '$show_quick_login='."'".$show_quick_login."';\r\n".
    '$quick_login_timeout='."'".$quick_login_timeout."';\r\n".
    '$set_system_timeout_overwrite='."'".$set_system_timeout_overwrite."';\r\n".
    '$set_password_requirements='."'".$set_password_requirements."';\r\n //requirements length:8 , minimum of at least 1 upper case, lower case and a number \r\n".
    '?>';
    
    
    fwrite($ecadphpconfigfile, $ecadphpconfigStandard);
    fclose($ecadphpconfigfile);
}

?><?php
    //User Administration -----------------------------------------------------------------------------
    function create_user($user,$ECAD_PHP_fileviewer_X_data_folder){
        
        $toCreateUsername = getSafeString($user);
        //this function only creates the folder and file structure of the user. use edit_user to configure the user.
        
        ecad_php_log($ECAD_PHP_fileviewer_X_data_folder,"INFO","user created ".'['.$toCreateUsername.']');
        //create user folders
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername);
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/data');
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/downloadpreperation');
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/sessions');
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/uploadtmp');
        
        
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php ?>';
        fwrite($ecad_php_user_config_file, $user_config_file_Standard);
        fclose($ecad_php_user_config_file);

        //create user share data
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/shares');



        //add share fav list
        mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/favorite_shares');

    }

function edit_user($datarootpath, $username, $new_password, $new_can_change_password, $new_email, $new_can_reset_password, $new_can_change_email, $new_re_routed_user_path, $new_can_upload, $new_can_delete, $new_amountOfAllowedShares,$new_can_use_short_share,$new_can_create_public_shares,$new_can_use_quick_login){
    
    
    $notify_user_for_update='false';

    
    ecad_php_log($datarootpath,"INFO","user edited ".'['.$username.']');
    //set vaues for if they arent set in userconfig.php
    $canAccessSystemFolder = false;
    $userIsAdmin = false;
    include $datarootpath."/users/".$username."/userconfig.php";
    
    $canAccessSystemFolder = (isset($canAccessSystemFolder) && $canAccessSystemFolder ? "true" : "false");
    $userIsAdmin= (isset($userIsAdmin) && $userIsAdmin ? "true" : "false");
    
    if ($new_password ==""){
        //keep password
    }else{
        //change password
        $userpasswordHash = password_hash($new_password, PASSWORD_DEFAULT);
        //reset sessions
        rrmdir($datarootpath.'/users/'.$username.'/sessions');
    }

    
    $ecad_php_user_config_file = fopen($datarootpath.'/users/'.$username.'/userconfig.php', "w");
    
    $user_config_file_Standard = '<?php'."\r\n".
    '$userpasswordHash='."'".$userpasswordHash."'".";\r\n".
    '$userIsAdmin= '.$userIsAdmin.";\r\n".
    '$canAccessSystemFolder='.$canAccessSystemFolder.";\r\n".
    '$can_change_password='.$new_can_change_password.";\r\n".
    '$email='."'".$new_email."'".";\r\n".
    '$can_reset_password='.$new_can_reset_password.";\r\n".
    '$can_change_email='.$new_can_change_email.";\r\n".
    '$re_routed_user_path='."'".$new_re_routed_user_path."'".";\r\n".
    '$can_upload='.$new_can_upload.";\r\n".
    '$can_delete='.$new_can_delete.";\r\n".
    '$amountOfAllowedShares='.$new_amountOfAllowedShares.";\r\n".
    '$can_use_short_share='.$new_can_use_short_share.";\r\n".
    '$can_create_public_shares='.$new_can_create_public_shares.";\r\n".
    '$can_use_quick_login='.$new_can_use_quick_login.";\r\n".
    '$notify_user_for_update='."'".$notify_user_for_update."'".";\r\n".
    '$can_login='."true".";\r\n".
    '?>';
    fwrite($ecad_php_user_config_file, $user_config_file_Standard);
    fclose($ecad_php_user_config_file);

    
    
}
function editConfigPHP(){
    //TODO
    
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
        file_put_contents($ECAD_PHP_fileviewer_X_data_folder."/ecadPHPLog.log",$log_text,FILE_APPEND);
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
            $path_array = explode('/',$path);
            
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
            return 1;
        }
        else
        {
            echo ("Folder   ");
            return 0;
        }
    }
    function printFileEditUploadDeleteCreateButtons($can_delete, $can_upload){
        echo "\r\n".'<form method="POST" action="" enctype="multipart/form-data">';
        echo '<input name="download_multiple" value="download" type="submit">';
        if($can_delete){ echo ' <input name="rename_FolderOrFile" value="rename" type="submit"> <input name="delete_FolderOrFile" value="delete" type="submit"> <input name="create_Folder" value="new folder" type="submit">';}
        if($can_upload){ echo ' <button type="button" onclick="showUploadFunction()">upload</button> <input name="create_TXT_File" value="new .txt file" type="submit">';}

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
            $isitaFile = printFileSize($fullpath, $file);
            
            //path system for shown files and folders
            $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?path=".$path;
            
            echo '<a href="'.$newpath.$file.'">'.$file."       ".'</a><span style="padding-left:20px"></span>';
            
            
            
            //make edit and view
            
            if($isitaFile == 1){
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                switch($ext)
                {
                    case "bmp":
                    case "jpg":
                    case "gif":
                    case "png":
                    case "svg":
                    case "jpeg":
                    case "pdf": echo'<a href="'.$newpath.$file.'&view"> view</a>'; break;
                        
                    //case "txt": echo'<a href="'.$newpath.$file.'&view"> edit</a>'; break;
                        
                        //case "jpg": echo'<a href="'.$newpath.$file.'&view"> edit</a>'; break;
                        
                        

                        
                    case "": // Handle file extension for files ending in '.'
                    case NULL: // Handle no file extension
                        break;
                }
            }
            
            
            
            
            echo '</br>';
            
            
            
        }
        return 1;
    }
    //end of User interface Printer -----------------------------------------------------------------------------------------------------------------------------------
?><?php
    //user functions for (upload, download, create file, delete, change name) -----------------------------------------------------------------------------------------
    function upload_multiple_file($datarootpath, $log_fileUpload, $fullpath, $path, $maximalUploadSize, $T_maximalUploadSize){
        
        //set php parameters
        ini_set ( 'post_max_size' , $T_maximalUploadSize );
        ini_set ( "upload_max_filesize" , $T_maximalUploadSize );
        //-----
        $count = 0;
        $failedUploadCount = 0;
        $totalUploadFileSizes = 0;
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
                        echo "file: &nbsp&nbsp".basename($name)."&nbsp&nbsp uploaded to: ".$path."</br>";
                        $count++; // Number of successfully uploaded file
                        
                        if($log_fileUpload){
                            ecad_php_log($datarootpath,"INFO","file uploaded ".'['.$path.'/'.$name.']['.filesize($fullpath.'/'.$name).'bytes]');
                            $totalUploadFileSizes += filesize($fullpath.'/'.$name);
                        }
                    }else{
                        echo "!!ERROR!! there was a problem uplaoding the file: $name </br>";
                        
                        if($log_fileUpload){
                            ecad_php_log($datarootpath,"ERROR","upload error!! (file not found in destination) ".'['.$path.'/'.$name.']['.filesize($fullpath.'/'.$name).'bytes]');
                            $totalUploadFileSizes += filesize($fullpath.'/'.$name);
                            $failedUploadCount++;
                        }
                    }
                }
            }
        }
        //check if sucessfully uploaded
        if($count > 0){
            ecad_php_log($datarootpath,"INFO","Files uploaded: ".'['.$count.' files][failed: '.$failedUploadCount.'][total size: '.$totalUploadFileSizes.']');
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
            
            //close zip
            $zip->close();
            
            //clean output puffer
            ob_end_clean();
            
            //set http header for download
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename="'.$zipname.'"');
            header('Content-Length: ' . filesize($datarootpath.'/'.$zipname));
            
            //send the zip
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
                    
                    echo "".'<input type="text" name="'.'file_'.$file_in_html.'" value="'.$file.'" style="display: none"></input>'.$file.'<br/>';
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
    function makeDownload($fullpath, $path, $isAviewableFile){
        //do download
        
        
        $filename = substr($path, strrpos($path, '/') + 1);
        
        substr($path, strrpos($path, '/') + 1);
        
        makeDownloadHead($fullpath, filetype($fullpath),$filename, $isAviewableFile);
        
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
<input style="margin-top:16px;" type="submit" name="submit_login" value="login"></input>
</form>
</div>

<?php

    }
    //show quick login
    global $show_quick_login;
    if($show_quick_login) printQuickLoginWindow();
    
    
    //give warning if login was unsucessfull and log it
    if(isset($_POST['user'])){
        ecad_php_log($datarootpath,"WARNING","unsucessful login for ".'['.$_POST['user'].']');
        echo '<div style="text-align:center; margin= 0 auto;"><a>username or password incorect</a></div>';
    }
    

            //show password reset
            global $show_password_reset_button;
            if($show_password_reset_button){
                echo'<div style="text-align:center; margin= 0 auto;"><a href="index.php?restorepassword"> forgot password</a></div></br>';
            }
            
            
    echo '</body></html>';
    }

    
    function handelLoginAccepted($datarootpath, $user, $pass, $secret_word, $loginType){
        //when login is accepted
        //using username password secret word and time as seed for the coockie generation
        $cookieStore = md5($user.$pass.$secret_word.time());
        $newUserCockies = $user.','.$cookieStore;
        
        //save session as active
        mkdir($datarootpath.'/users/'.$user.'/sessions/'.$cookieStore, 0777, true);
        
        
        //set first login
        //get IP
        $client_Address = $_SERVER['REMOTE_ADDR'];
        //get time
        $current_time = date("Y.m.d-H.i.s",time());
        //write to file
        file_put_contents($datarootpath.'/users/'.$user.'/sessions/'.$cookieStore.'/first_seen.txt', $current_time.';'.$client_Address.';'.$loginType);
        
        
        //set cookie on client
        setcookie('ECAD_PHP_fileviewer_login',$newUserCockies);

        echo "you are logged in      please wait.......";
        header("Refresh:0; url=index.php?path=");
        ecad_php_log($datarootpath,"INFO","user logged in ".'[new cockie: '.$newUserCockies.']');

    }

function removeLoginCockieFromServer($datarootpath, $c_username){
    //delete session from server
    
    list($c_username,$cookie_hash) = explode(',',$_COOKIE['ECAD_PHP_fileviewer_login']);
    //clean input
    $c_username = getSafeString($c_username);
    $cookie_hash = getSafeString($cookie_hash);
    rrmdir($datarootpath.'/users/'.$c_username.'/sessions/'.$cookie_hash);
    
    
    //delete cockie from client
    setcookie('ECAD_PHP_fileviewer_login',"",-1);
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
        echo'</br><form method="POST" action=""><input name="create_user" value="new user" type="submit"></form></br>';
        if($canAccessSystemFolder){
            echo '<a href="index.php?systemsettings"> change system settings </a></br></br>';
        }
        
        
        echo '<a href="index.php?action=getLogFile"> download log file </a>';
        if($canAccessSystemFolder){
            echo '<span style="padding-left:80px"></span><a href="index.php?path=/"> root file browser </a>';
        }
        
    }
    function logout_user($datarootpath){
        if($_POST['user_to_delete'] != ""){
            $user_tmp = getSafeString($_POST['user_to_delete']);
            ecad_php_log($datarootpath,"INFO"," logged out by admin".'['.$_POST['user_to_delete'].']');
            
            rrmdir($datarootpath.'/users/'.$user_tmp.'/sessions');
        }
    }



function edit_user_submit($datarootpath){
    $username = getSafeString($_POST['username']);
    if($username != "" && file_exists($datarootpath."/users/".$username)){
        
        
        //(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false")

        $username = $username;
        $password = $_POST['password'];
        $can_change_password =(isset($_POST['can_change_password']) && $_POST['can_change_password']  ? "true" : "false");
        $email = $_POST['email'];
        $can_reset_password =(isset($_POST['can_reset_password']) && $_POST['can_reset_password']  ? "true" : "false");
        $can_change_email =(isset($_POST['can_change_email']) && $_POST['can_change_email']  ? "true" : "false");
        $re_routed_user_path = $_POST['re_routed_user_path'];
        $can_upload =(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false");
        $can_delete =(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false");
        $amountOfAllowedShares = $_POST['allowed_shares'];
        $can_use_short_share =(isset($_POST['can_use_short_share']) && $_POST['can_use_short_share']  ? "true" : "false");
        $can_create_public_shares =(isset($_POST['can_create_public_shares']) && $_POST['can_create_public_shares']  ? "true" : "false");
        $can_use_quick_login =(isset($_POST['can_use_quick_login']) && $_POST['can_use_quick_login']  ? "true" : "false");
        
        
        edit_user($datarootpath, $username, $password, $can_change_password, $email, $can_reset_password, $can_change_email, $re_routed_user_path, $can_upload, $can_delete, $amountOfAllowedShares,$can_use_short_share,$can_create_public_shares,$can_use_quick_login);
        

    }else{
            ecad_php_log($datarootpath,"ERROR","user couldn't be eddited ".'['.$_POST['username'].']');
    }
}
function printEditUserView($datarootpath){
    //store curent user
    $current_administrative_user = $user;
    //load to edit user
    include $datarootpath."/users/".$_POST['user_to_delete']."/userconfig.php";

    
    echo '</br><form method="POST" action="">Username: <input type="text" name="username" value="'.$_POST['user_to_delete'].'" readonly></input><br/><br/>';
    

    echo 'Password: <input type="text" name="password">(left empty to keep password)</input><br/>';
    if ($can_change_password){
        echo 'User can change his password: <input type="checkbox" name="can_change_password" value="can_change_password" checked></br></br>';
    }else{
        echo 'User can change his password: <input type="checkbox" name="can_change_password" value="can_change_password"></br></br>';
    }
    
    
    echo 'Email: <input type="text" name="email" value="'.$email.'"></input><br/>';
    if ($can_reset_password){
        echo 'User can reset password: <input type="checkbox" name="can_reset_password" value="can_reset_password" checked></br>';
    }else{
        echo 'User can reset password: <input type="checkbox" name="can_reset_password" value="can_reset_password"></br>';
    }
    if ($can_change_email){
        echo 'User can change his email: <input type="checkbox" name="can_change_email" value="can_change_email" checked></br></br>';
    }else{
        echo 'User can change his email: <input type="checkbox" name="can_change_email" value="can_change_email"></br></br>';
    }
    
    

    echo 'User Path re routing: <input type="text" title="enter a share that the user will use as home directory (if left empty will use the data directory of the user)" name="re_routed_user_path" value="'.$re_routed_user_path.'"></br>';
    if ($can_upload){
        echo 'can upload: <input type="checkbox" name="can_upload" value="can_upload" checked></br>';
    }else{
        echo 'can upload: <input type="checkbox" name="can_upload" value="can_upload"></br>';
    }
    if ($can_delete){
        echo 'can delete / edit: <input type="checkbox" name="can_delete" value="can_delete" checked></br></br>';
    }else{
        echo 'can delete / edit: <input type="checkbox" name="can_delete" value="can_delete"></br></br>';
    }
    
    
    echo 'amount of allowed shares: <input type="text" name="allowed_shares" value="'.$amountOfAllowedShares.'"></input> (10 recomendet)</br>';
    if ($can_use_short_share){
        echo 'User can use a short share name under the path /s/....: <input type="checkbox" name="can_use_short_share" value="can_use_short_share" checked></br>';
    }else{
        echo 'User can use a short share name under the path /s/....: <input type="checkbox" name="can_use_short_share" value="can_use_short_share"></br>';
    }
    if ($can_create_public_shares){
        echo 'User can create shares that are publicly accesable: <input type="checkbox" name="can_create_public_shares" value="can_create_public_shares" checked></br></br>';
    }else{
        echo 'User can create shares that are publicly accesable: <input type="checkbox" name="can_create_public_shares" value="can_create_public_shares"></br></br>';
    }
    
    
    if ($can_use_quick_login){
        echo 'User can use quick login: <input type="checkbox" name="can_use_quick_login" value="can_use_quick_login" checked> (has also to be globaly activated)</br>';
    }else{
        echo 'User can use quick login: <input type="checkbox" name="can_use_quick_login" value="can_use_quick_login"> (has also to be globaly activated)</br>';
    }
    
    
    echo '</br><input name="edit_user_submit" value="OK" type="submit"></form>';
}
function printCreateUserView(){
    echo '</br><form method="POST" action="">Username: <input type="text" name="username"></input><br/>Password: <input type="text" name="password"></input><br/>';
        echo 'User can change his password: <input type="checkbox" name="can_change_password" value="can_change_password"></br></br>';
    echo 'Email: <input type="text" name="email"></input><br/>';
        echo 'User can reset password: <input type="checkbox" name="can_reset_password" value="can_reset_password"></br>';
        echo 'User can change his email: <input type="checkbox" name="can_change_email" value="can_change_email"></br></br>';
    echo 'User Path re routing: <input type="text" title="enter a share that the user will use as home directory (if left empty will use the data directory of the user)" name="re_routed_user_path" value="'.$re_routed_user_path.'"></br>';
        echo 'can upload: <input type="checkbox" name="can_upload" value="can_upload"></br>';
        echo 'can delete / edit: <input type="checkbox" name="can_delete" value="can_delete"></br></br>';
    echo 'amount of allowed shares: <input type="text" name="allowed_shares" value="0"></input> (10 recomendet)</br>';
        echo 'User can use a short share name under the path /s/....: <input type="checkbox" name="can_use_short_share" value="can_use_short_share"></br>';
        echo 'User can create shares that are publicly accesable: <input type="checkbox" name="can_create_public_shares" value="can_create_public_shares"></br></br>';
        echo 'User can use quick login: <input type="checkbox" name="can_use_quick_login" value="can_use_quick_login"> (has also to be globaly activated)</br>';
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
                    $mySharesList .= '<form method="POST" action="" style="margin-bottom: 0px;">'.$shareName.'<span style="padding-left:20px"></span> (owner: '.$shareCreatorName.')<span style="padding-left:20px"></span><a href="index.php?share='.$shareID.'/">browse share</a><span style="padding-left:10px"></span><input name="edit_share" value="edit" type="submit"><input name="delete_share" value="delete" type="submit"><span style="padding-left:20px"></span> (shareID: '.$shareID.')<input name="shareToEdit" value="'.$shareID.'" type="hidden"></form>';
                }else{
                    $otherSharesCounter++;
                    $otherSharesList .= '<form method="POST" action="" style="margin-bottom: 0px;">'.$shareName.'<span style="padding-left:20px"></span> (owner: '.$shareCreatorName.')<span style="padding-left:20px"></span><a href="index.php?share='.$shareID.'/">browse share</a><span style="padding-left:10px"></span><span style="padding-left:20px"></span> (shareID: '.$shareID.')<input name="selectedShareToEdit" value="'.$shareID.'" type="hidden"></form>';
                    
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
function printUserInterfaceShareFileViewer($shareID, $user, $path, $fullpath, $datarootpath, $can_delete, $can_upload, $nichtgelisteteDatein, $ecad_php_version, $shareName, $T_shareID){
    
    //prints head of user interface
    printUserHeader($user, $ecad_php_version);
    
    //print share name and id
    echo 'share: '.$shareName.'&nbsp&nbsp&nbsp&nbsp( ID: '.$T_shareID.' )</br>';
    
    //prints new path display system
    printUserPathForShare($shareID.$path);
    
    if(file_exists($fullpath.'/')){
        //logs the file request
        ecad_php_log($datarootpath,"INFO","shared folder request ".'['.$T_shareID.$path.']');
        
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
        $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?share=".explode('/',$path)[0].'/';
        
        echo "path: ";
        $path_array = explode('/',$path);
        
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
        ecad_php_log($datarootpath,"WARNING","edit user in share without permissions ".'[ID:'.$shareID.']');
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
    echo '</br><span style="padding-left:20px"></span><a href="index.php?settings">settings</a></br>';
    
    if($GLOBALS['allow_quick_login'] && $GLOBALS['can_use_quick_login']){
        
        echo '</br><form method="POST" action="" style="margin-bottom: 0px;">';
        //echo '<input name="quickLogin" value="getNew" type="hidden">';
        
        echo 'Quick Login: <input name="quickLoginCode" value="" type="text" titel="enter the quick login key on the login page or go to index.php?quick and enther the given quick login key here to login (you will have to refresh the page"> ';
        echo ' <input name="quickLoginAction" value="login" type="submit">';
        
        //make quick login
        
        if(isset($_POST["quickLoginAction"])) echo '<span style="padding-left:20px"></span>'.logintoquicklogin($user,getSafeString($_POST["quickLoginCode"]));
        
        
        
        
        
        
        echo '</form>';
   
        
    }
    //echo '</br><span style="padding-left:20px"></span><a href="index.php?administration">administration panel</a> (not implemented)</br>';
}
?><?php
    //user settings ----------------------------------------------------------------------------------------------------------------------------------------------------
    //TODO
    //show
    
    
    //submit
    //TODO
    
    
    
    //show sessions
    //TODO
    function listActiveSessions(){
        global $datarootpath, $user, $cookie_hash,$nichtgelisteteDatein;
        //TODO (sorting, closing sessions)
        echo 'Active Sessions: </br>';
        $files = scandir($datarootpath.'/users/'.$user.'/sessions/');
        
        $sessions = array();
        $sessionCount = 0;
        
        //read sessions
        foreach($files as $file){
            if (!in_array( $file , $nichtgelisteteDatein )){
                
                //read files
                $sessionContent_lastSeen = file_get_contents($datarootpath.'/users/'.$user.'/sessions/'.$file.'/last_seen.txt');
                $sessionContent_FirstSeen = file_get_contents($datarootpath.'/users/'.$user.'/sessions/'.$file.'/first_seen.txt');
                
                //handel problems
                if(!$sessionContent_lastSeen) $sessionContent_lastSeen = '_;_';
                if(!$sessionContent_FirstSeen) $sessionContent_FirstSeen = '_;_;normal login';
                
                
                list($lastSeen_time,$lastSeen_IP) = explode(';',$sessionContent_lastSeen);
                list($firstSeen_time,$firstSeen_IP,$loginType) = explode(';',$sessionContent_FirstSeen);
                
                //array_push($sessions,array($lastSeen_time,$lastSeen_IP,$firstSeen_time,$firstSeen_IP));
                
                $sessions[0][$sessionCount] = $lastSeen_time;
                $sessions[1][$sessionCount] = $lastSeen_IP;
                $sessions[2][$sessionCount] = $firstSeen_time;
                $sessions[3][$sessionCount] = $firstSeen_IP;
                $sessions[4][$sessionCount] = $file;
                $sessions[5][$sessionCount] = $loginType;
                
                $sessionCount++;
            }
        }
        
        //sort sessions
        array_multisort($sessions[0], SORT_DESC, SORT_STRING, $sessions[1],$sessions[2],$sessions[3],$sessions[4],$sessions[5]);
        
        //print sessions
        for ($i = 0; $i < $sessionCount; $i++) {
            
            //check if current session
            if($sessions[4][$i] === $cookie_hash){
                echo ($i+1).'<span style="padding-left:20px">Current Session: <span style="padding-left:122px">ip:  '.$sessions[1][$i].'<span style="padding-left:60px"> original login: '.$sessions[2][$i].'<span style="padding-left:20px">ip: '.$sessions[3][$i].'<span style="padding-left:20px">login Type: '.$sessions[5][$i].'</br></br>';
            }else{
                echo '<form method="POST" action="">';
                echo ($i+1).'<span style="padding-left:20px">last seen at: '.$sessions[0][$i].'<span style="padding-left:20px">ip:  '.$sessions[1][$i].'<span style="padding-left:60px"> original login: '.$sessions[2][$i].'<span style="padding-left:20px">ip: '.$sessions[3][$i].'<span style="padding-left:20px">login Type: '.$sessions[5][$i].'<span style="padding-left:20px"> ';
                
                echo '<input type="hidden" name="close_session_submit_name" value="'.$sessions[4][$i].'" type="submit"><input name="close_session_submit" value="close session" type="submit"></form>';
                echo '</br></br>';
            }
            
            
            

            
            
        }
    }

    

    
    //close one specific session
    function closeSessionFromSettings(){
        global $datarootpath, $user;
        $sessionToClose = getSafeString($_POST["close_session_submit_name"]);


        rrmdir($datarootpath.'/users/'.$user.'/sessions/'.$sessionToClose.'/');

    }
    
    //close all sessions except current
    //TODO
    
    
?><?php
    //Password restore/reset password ----------------------------------------------------------------------------------------------------------------------------------------------------
    //TODO

    //resetPassword
    //TODO
    //resetPassword submit
    //TODO
    
    //newPassword=code
    //TODO
    //newPassword=code submit
    //TODO
    


    
?><?php
    //quick login ----------------------------------------------------------------------------------------------------------------------------------------------------
    //TODO
    
    
    //get current quickKey
    //TODO
    function getCurrentQuickLoginKey(){
        global $datarootpath;

        //check if key exists on client and if it is still working
        
        $newQuckKey = rand(10000000, 99999999);
        //get some numbers for check sum
        $newQuickKeyHash =  substr(crc32($newQuckKey), 1, 2);
        
        //repeat if allready exists
        while(file_exists($datarootpath.'/quicklogin/'.$newQuckKey.$newQuickKeyHash.'/')){ $newQuckKey = rand(10000000, 99999999); $newQuickKeyHash =  substr(crc32($newQuckKey), 1, 2);}
        
        mkdir($datarootpath."/quicklogin/".$newQuckKey.$newQuickKeyHash, 0777, true);
        file_put_contents($datarootpath."/quicklogin/".$newQuckKey.$newQuickKeyHash.'/config.php', '<?php $quickKeyUser="";   $quickKeyCreationTime='.round(microtime(true)).'     ?>');
        setcookie('ECAD_PHP_fileviewer_quickkey',$newQuckKey.$newQuickKeyHash);

        return substr($newQuckKey, 0, 4).' '.substr($newQuckKey, 4).' '.$newQuickKeyHash;

    }
    
    //check quick key for validity (a quick key works for 60 seconds)
    //TODO
    function quickKeyCheckForLoginOrDelete(){
        global $datarootpath;

        if(isset($_COOKIE['ECAD_PHP_fileviewer_quickkey'])){

             $quickKey = getSafeString($_COOKIE['ECAD_PHP_fileviewer_quickkey']);
            //check if key exists on client and if it is still working
            
            if(file_exists($datarootpath.'/quicklogin/'.$quickKey.'/config.php')){

                include $datarootpath.'/quicklogin/'.$quickKey.'/config.php';
            }else{

                return false;
            }

            //check if key has timed out
            if($quickKeyCreationTime+60 < round(microtime(true))){
                

                //remove key from server
                rrmdir($datarootpath.'/quicklogin/'.$quickKey);
                

                
                return false;
            }else{
                //quick key is valid check for user
                if($quickKeyUser !=''){
                    //login key accepted
                    echo 'login via quick login....</br>';
                    
                    //remove key on client
                    setcookie('ECAD_PHP_fileviewer_quickkey','',-1);
                    //remove key on server
                    rrmdir($datarootpath.'/quicklogin/'.$quickKey);

                    
                    //logging in user
                    handelLoginAccepted($datarootpath, $quickKeyUser, 'npw', $secret_word, 'quickLogin');

                    return true;
                }else{
                    rrmdir($datarootpath.'/quicklogin/'.$quickKey);

                    return false;
                }
            }
        }


        return false;
    }
function removeQuickLoginKeyFromServer($quickKey){
    global $datarootpath;
    if(file_exists($datarootpath.'/quicklogin/'.$quickKey.'/config.php')){
        rrmdir($datarootpath.'/quicklogin/'.$quickKey);
    }
}


    //user input for quick login
    function logintoquicklogin($username, $quickKey){
        global $datarootpath;
        $quickKey = preg_replace('/[^0-9]/i', '', $quickKey);
        //$quickKey = str_replace(" " , "" , $quickKey);
        if(strlen($quickKey) > 3){

            if(file_exists($datarootpath.'/quicklogin/'.$quickKey.'/config.php')){
  
                include $datarootpath.'/quicklogin/'.$quickKey.'/config.php';
            }else{

                return 'invalid quick key';
            }

            file_put_contents($datarootpath.'/quicklogin/'.$quickKey.'/config.php', '<?php $quickKeyUser="'.$username.'";   $quickKeyCreationTime='.$quickKeyCreationTime.'     ?>');
            return 'login accepted <span style="padding-left:20px"></span> (you may have to press the refresh button to complete the login)';
        }

        return 'please enter a valid key';

    }

//only check if key was accepted
function quickKeyCheckForLogin($quickKey){
    global $datarootpath;
    $quickKey = preg_replace('/[^0-9]/i', '', $quickKey);

        //check if key exists on client and if it is still working
        
        if(file_exists($datarootpath.'/quicklogin/'.$quickKey.'/config.php')){
            include $datarootpath.'/quicklogin/'.$quickKey.'/config.php';
            if($quickKeyUser !=''){
                return 'true';
            }

        }
            
            return 'false';
    
}
    
//
function printQuickLoginWindow(){
    {
        $quickkeytmp = getCurrentQuickLoginKey();
        if (isset($_GET['quick'])) $redirectPath = "?quick";
        echo '<div style="text-align:center; margin= 0 auto;"><form method="POST" action="index.php'.$redirectPath.'">Quick login key: <div id="quickKey" style="display: inline">'.$quickkeytmp.'</div> <input  type="submit" name="refresh" value="refresh"></input> <div id="quickKeyTimeout" style="display: inline">55s</div></form></div>';
        
        //Autorefresh
        //TODO
        ?>
        <script language="JavaScript">
        var quickKeyTimeoutCounter = 54;
        var refreshCount = 1;
        var refreshactive = true;
        var currentQuickKey = "<?php echo $quickkeytmp; ?>";
        function checkForQuickLoginConfirm(){
            if(quickKeyTimeoutCounter < 2){
                if(refreshactive){
                    refreshactive = false;
                    quickKeyTimeoutCounter = 0;
                    document.getElementById("quickKeyTimeout").innerHTML = quickKeyTimeoutCounter+"s";
                    console.log("key has timed out. getting new one");
                    
                    //dont show key anymore
                    document.getElementById("quickKey").innerHTML = "0000 0000 00";
                    
                    //get new key    generateNewQuickKey
                    
                    var client = new XMLHttpRequest();
                    client.open('GET', '?gnqk');
                    
                    client.onreadystatechange = function() {
                        var currentStatus = client.status;
                        var curretnResponse = client.responseText;
                        if (currentStatus == "200" && curretnResponse != ""){
                            console.log("recived new key");
                            currentQuickKey = client.responseText;
                            
                            document.getElementById("quickKey").innerHTML = currentQuickKey;
                            quickKeyTimeoutCounter = 55;
                            document.getElementById("quickKeyTimeout").innerHTML = quickKeyTimeoutCounter+"s";
                            refreshactive = true;
                        }else{
                            console.log("status not 200 or empty answer. status: "+currentStatus +" Answer: "+curretnResponse);
                        }
                    }
                    client.send();
                }

            }else if(refreshactive){
                quickKeyTimeoutCounter--;
                document.getElementById("quickKeyTimeout").innerHTML = quickKeyTimeoutCounter+"s";
                if(refreshCount >2){
                    
                    var client = new XMLHttpRequest();
                    client.open('GET', '?cfqlc');
                    
                    client.withCredentials = false;
                    
                    client.onreadystatechange = function() {
                        var response = client.responseText;
                        console.log("response: "+response);
                        if(client.status == "200" && response == "true"){
                            console.log("refrshing page");
                            window.location.href = window.location.href;
                            
                        }
                    }
                    client.send();
                    
                    refreshCount = 0;
                }
                refreshCount++;
            }
            
            
        }
        var quickloginfreshner=setInterval(checkForQuickLoginConfirm,1000);
        
        </script>
        
        <?php
        
        
    }
}




?>
