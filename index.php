<?php
    
    $debug = false;
    $secret_word = "word";
    $ecad_php_version ="ECAD PHP fileviewer v0.1.15b";
    $ecad_php_version_number = "v0.1.15b";
    installifneeded($secret_word, $ecad_php_version_number);
$show_ecad_php_version_on_title = true;

    //load config
    include "config.php";
    //show error (activate only in debug mode!)
    error_reporting(1);
    //auto login as default user user0
    //setcookie('ECAD_PHP_fileviewer_login',"user0".','.md5("admin".$secret_word)); if (!$_COOKIE['ECAD_PHP_fileviewer_login']) {header("Refresh:0; url=index.php");}

    //authentification service cockie
    $authentificated = false;
    
    
    if ($_COOKIE['ECAD_PHP_fileviewer_login']) {
        list($c_username,$cookie_hash) = split(',',$_COOKIE['ECAD_PHP_fileviewer_login']);
        
        if(file_exists($datarootpath."/users/".$c_username)){
            include $datarootpath."/users/".$c_username."/userconfig.php";
            include $datarootpath."/users/".$c_username.'/login.php';

            if (strstr($acceptableuserLoginCockies, "-".$_COOKIE['ECAD_PHP_fileviewer_login']."-")){
                $user = $c_username;
                $userpath="/".$user;
                
                $authentificated = true;
            } else {
                $authentificated = false;
            }
        }
        //logout (server)
        if($_GET["action"] == "logout"){
            //delete session from server
            $str3706849=file_get_contents($datarootpath."/users/".$c_username.'/login.php');
            

            $str3706849=str_replace('<?php $acceptableuserLoginCockies = $acceptableuserLoginCockies."'.$_COOKIE['ECAD_PHP_fileviewer_login'].'-"; ?>', '',$str3706849);
            

            file_put_contents($datarootpath."/users/".$c_username.'/login.php', $str3706849);
            
            //delete cockie from client
            setcookie('ECAD_PHP_fileviewer_login',"null");
            $authentificated = false;
            header("Refresh:0; url=index.php");
        }
        
    }else{
        $authentificated = false;
    }
    //remove login cockie
    if($_GET["action"] == "logout"){
        //from client
        setcookie('ECAD_PHP_fileviewer_login',"null");
        $authentificated = false;
        header("Refresh:0; url=index.php");
    }
    //-------------------
    if ($authentificated) {
        if($userIsAdmin||$user=="admin"){
            //admin user Interface
            $show_user_interface = true;
            echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
            echo '<html xmlns="http://www.w3.org/1999/xhtml">';
            echo '<head>';
            echo '<title>'.$ecad_php_version.'</title>';
            echo '</head>';
            echo '<body>';
            
            echo $ecad_php_version.'    <a href="index.php?action=logout"> logout </a></br>';
            echo "user: ".$user." (you are adminnistrator)</br>";
            
            //Aktionen
            if ( isset( $_POST['delete_user'] ) ) {
                $show_user_interface = false;
                echo'</br><form method="POST" action="">Really delete user: '.$_POST['user_to_delete'].'?<span style="padding-left:80px"></span><input type="hidden" name="user_to_delete" value="'.$_POST['user_to_delete'].'"><input name="really_delete_user" value="delete" type="submit"><input name="" value="abort" type="submit"></form>';
            }
            if ( isset( $_POST['really_delete_user'] ) ) {
                rrmdir($datarootpath."/users/".$_POST['user_to_delete']);
            }
            
            if ( isset( $_POST['create_user'] ) ) {
                $show_user_interface = false;
                echo '</br><form method="POST" action="">Username: <input type="text" name="username"></input><br/>Password: <input type="text" name="password"></input><br/>';
                echo 'can upload:<input type="checkbox" name="can_upload" value="is admin"></br>';
                echo 'can delete / edit:<input type="checkbox" name="can_delete" value="is admin"></br>';
                echo '<input name="create_user_submit" value="OK" type="submit"></form>';
            }
            if ( isset( $_POST['edit_user'] ) ) {
                $show_user_interface = false;
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
            if ( isset( $_POST['create_user_submit'] ) ) {
                if($_POST['username'] != ""){
                create_user($_POST['username'],$_POST['password'],$datarootpath,$secret_word,(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false"),(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false"));
                }
            }
            if ( isset( $_POST['edit_user_submit'] ) ) {
                if($_POST['username'] != "" && file_exists($datarootpath."/users/".$_POST['username'])){
                    
                    if ($_POST['password'] ==""){
                        $current_administrative_user = $user;
                        include $datarootpath."/users/".$_POST['username']."/userconfig.php";
                        edit_user_keep_password($_POST['username'],$userpasswordHash,$datarootpath,$secret_word,(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false"),(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false"));
                        include $datarootpath."/users/".$current_administrative_user."/userconfig.php";
                    }else{
                
                edit_user($_POST['username'],$_POST['password'],$datarootpath,$secret_word,(isset($_POST['can_upload']) && $_POST['can_upload']  ? "true" : "false"),(isset($_POST['can_delete']) && $_POST['can_delete']  ? "true" : "false"));
                    }
                }
            }
            if ( isset( $_POST['logout_user'] ) ) {
                //close all sessions of the user
                if($_POST['user_to_delete'] != ""){
                    $ecad_php_user_config_file = fopen($datarootpath.'/users/'.$_POST['user_to_delete'].'/login.php', "w");
                    $user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
                    fwrite($ecad_php_user_config_file, $user_config_file_Standard);
                    fclose($ecad_php_user_config_file);
                    //echo "user logged out";
                }
            }
            //--------------------
            //output
            if($show_user_interface){
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
                            echo'<form method="POST" action="">'.$file.'<span style="padding-left:80px"></span>   <input type="hidden" name="user_to_delete" value="'.$file.'"><input name="edit_user" value="edit" type="submit"><input name="delete_user" value="delete" type="submit"><span title="all sessions of this user will be closed"><input name="logout_user" value="logout" type="submit"></span></form>';
                        }
                    }
                }
                echo'</br><form method="POST" action=""><input name="create_user" value="new user" type="submit"></form>';
            }
           //---------------------------------------------
        }else{
            //normal user logged in
    //debug
    if($debug){
        echo "</br>--------------debug------------------";
        echo "</br>";
        echo "script: ".basename(__FILE__);
        echo "</br>";
        echo "script name: ".__FILE__;
        echo "</br>";
        echo "curPageURL: ".curPageURL();
        echo "</br>";
        echo "script directory: ".__DIR__;
        echo "</br>";
        echo "php self: ".$_SERVER['PHP_SELF'];
        
        echo "</br>server name: ".$_SERVER['SERVER_NAME'];
        echo "</br>".getcwd();
        
        echo "</br>-------------------------------------";
    }
        //get path
        $path = $_GET["path"];
    $path = str_replace ("%20" , " " , $path);
            
            
            
            
            
            //remove escape characters
            if ($path[strlen($path) - 1] != "/"){
                $path = $path."/";
            }
    $path = str_replace ("..\\" , " " , $path);
    $path = str_replace ("../" , " " , $path);
    $fullpath = $datarootpath."/users/".$userpath."/data".$path;
    //validate path
    if (strlen($path) >0){
        if ($path[0] != "/"){
            $path ="/".$path;
        }
    }
    $fullpath = $datarootpath."/users/".$userpath."/data".$path;
    if($debug){
    echo "</br>--------------debug------------------";
    echo "</br> original path: ".$path;
    echo "</br>-------------------------------------";
    }
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
        $fullpath = $datarootpath."/users/".$userpath."/data".$path;
    }
    //redo fullpath for file reading
    
    if($debug){
        echo "</br>--------------debug------------------";
        echo "</br> path: ".$path;
        echo "</br> fullpath: ".$fullpath;
        echo "</br>-------------------------------------";
        echo "</br></br></br></br>";
    }

    if(is_file($fullpath)){
        if($debug){
            echo "</br>--------------INFO-------------------";
            echo "</br>this is a file";
            echo "</br>-------------------------------------";
            echo "</br>";
        }
        //do download

        $filename = substr($path, strrpos($path, '/') + 1);
        
        substr($path, strrpos($path, '/') + 1);
        
        makeDownload($fullpath, filetype($fullpath),$filename);

        //clean the file reader
        ob_end_clean();
        //read file for download
        readfile($fullpath);
        //---------

    }else{
        //normal user -----------------------------------------
        $show_user_interface = true;
        //user input
        if ( isset( $_POST['create_Folder'] ) && $can_delete ) {
            $show_user_interface = true;
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
            
            //mkdir($fullpath.'/New Folder', 0777, true);
            //create new folder under $fullpath
            
            echo 'Created new Folder!<br/>';

        }
        if ( isset( $_POST['rename_FolderOrFile'] ) && $can_delete ) {
            //get files of current folder
            $show_user_interface = false;
            $nichtgelisteteDatein = array("index.php", ".htaccess", ".", "..");
            $files = scandir($fullpath.'/');
            echo "\r\n".'<form method="POST" action="">';
            $files_to_edit_counter = 0;
            foreach($files as $file){
                if (in_array ( $file , $nichtgelisteteDatein )){
                    
                }else{
                    $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
                    $edit_file_if = (isset($_POST[('file_'.$file_in_html)]) && $_POST[('file_'.$file_in_html)]  ? "true" : "false");
                    /*
                    echo 'file_'.str_replace (" " , "%20" , $file);
                    echo "</br>";
                    echo var_dump($_POST[('file_'.str_replace(" " , "%20" , $file))]);
                    echo "</br>";
                    echo var_dump(isset($_POST[('file_'.str_replace (" " , "%20" , $file))]));
                    echo "</br>";
                    echo var_dump($_POST[('file_'.str_replace (" " , "%20" , $file))]  ? "true" : "false");
                    echo "</br></br>";
                     */
                    //echo $edit_file_if;
                    //echo "".$file.'  -->    '.'<input type="text" name="'.'file_'.str_replace (" " , "%20" , $file).'" value="'.$file.'"></input><br/>';
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
        if ( isset( $_POST['rename_FolderOrFile_submit'] ) && $can_delete ) {
            //get files of current folder
            $show_user_interface = true;
            $nichtgelisteteDatein = array("index.php", ".htaccess", ".", "..");
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
        if($show_user_interface){
            //normal user Interface
            echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
            echo '<html xmlns="http://www.w3.org/1999/xhtml">';
            echo '<head>';
            echo '<title>'.$ecad_php_version.'</title>';
            echo '</head>';
            echo '<body>';
            
            echo $ecad_php_version.'    <a href="index.php?action=logout"> logout </a></br>';
            echo "user: ".$user."</br>";
            
            //new path display system
            
            if ($path == '/'){
                echo "</br> path: ";
                echo'<a href="'.'">root</a><a> /</a>';
            }else{
                $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?path=/";
                
                echo "</br> path: ";
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
            
            
            //old path display system
            //echo "</br> path: ".$path ."</br>";
            
            if(file_exists($fullpath.'/')){
                
                $nichtgelisteteDatein = array("index.php", ".htaccess", ".", "..");
                
                $files = scandir($fullpath.'/');
                sort($files, SORT_NATURAL); // this does the sorting
                
                $datein = 0;
                echo "\r\n".'<form method="POST" action="">';
                if($can_delete){ echo '<input name="rename_FolderOrFile" value="rename" type="submit"> <input name="delete_FolderOrFile" value="delete" type="submit" disabled> <input name="create_Folder" value="new folder" type="submit">';}
                if($can_upload){ echo ' <input name="upload_FolderOrFile" value="upload" type="submit" disabled>';}
                echo "</br></br>";
                foreach($files as $file){
                    
                    if (in_array ( $file , $nichtgelisteteDatein )){
                        
                        //files that are not listed for users
                    }else{
                        echo "\r\n";
                        $file_in_html = str_replace(".","%2E",str_replace (" " , "%20" , $file));
                        if($can_delete){ echo '<input type="checkbox" name='."'".'file_'.$file_in_html.''."'".' value="true"></input> ';}
                        
                        
                        $datein++;
                        //---------------------------------
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
                        //path system for shown files and folders
                        $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?path=".$path;
                        
                        echo '<a href="'.$newpath.$file.'">'.$file."       ".'</a> </br>';
                    }
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
                
                echo "</br> Folder not found";
                
            }
            echo "</body>";
            echo "</html>";
        }

    }
        

        //end ecad file view------------------------
        //authentification service login
    }
        }else{
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
                $newUserCockies = $user.','.md5($pass.$secret_word.time());
                //activate cockie
                $user_config_file_Standard = '<?php $acceptableuserLoginCockies = $acceptableuserLoginCockies."'.$newUserCockies.'-"; ?>';
                file_put_contents($datarootpath."/users/".$user.'/login.php', $user_config_file_Standard, FILE_APPEND);
                
                setcookie('ECAD_PHP_fileviewer_login',$newUserCockies);
                //setcookie('ECAD_PHP_fileviewer_login',$user.','.md5($pass.$secret_word));
                echo "you are logged in      please wait.......";
                header("Refresh:0; url=index.php?path=/");

            }
            else
            {
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
       echo '<div style="text-align:center; margin= 0 auto;"><a>username or password incorect</a></div>';
       }
    echo '</body></html>';
    }
    }
    
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

function makeDownload($file, $type, $filename) {
    
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
        $ecadphpconfigStandard = '<?php'."\r\n".'$datarootpath='."'".__DIR__.'/ECAD PHP fileviewer X data'."'".';'."\r\n".'$firstInstallationVersion='."'".$ecad_php_version_number."'".';'."\r\n".'$adminPassword="admin";'."\r\n".'?>'.'<?php'."\r\n".'$user='.'"user0";'."\r\n".'$userpath='.'"/user0";'."\r\n".'?>';
fwrite($ecadphpconfigfile, $ecadphpconfigStandard);
fclose($ecadphpconfigfile);
//ecad php data folder
mkdir('./ECAD PHP fileviewer X data/shares', 0777, true);
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
$user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5("admin".$secret_word)."'".';'."\r\n".'$userIsAdmin= true;'."\r\n".'$can_upload= true;'."\r\n".'$can_delete= true;'."\r\n".'?>';
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
                            //create user
                            mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername);
                            mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/data');
                            mkdir($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/downloadpreperation');
                            $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
                            $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5($toCreateUserPassword.$secret_word)."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload='.$toeditUser_can_upload.";\r\n".'$can_delete='.$toeditUser_can_delete.";\r\n".'?>';
                            fwrite($ecad_php_user_config_file, $user_config_file_Standard);
                            fclose($ecad_php_user_config_file);
                            //create user password
                            $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/login.php', "w");
                            $user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
                            fwrite($ecad_php_user_config_file, $user_config_file_Standard);
                            fclose($ecad_php_user_config_file);
                            
    }
?><?php
    function edit_user($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word,$toeditUser_can_upload,$toeditUser_can_delete){
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5($toCreateUserPassword.$secret_word)."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload='.$toeditUser_can_upload.";\r\n".'$can_delete='.$toeditUser_can_delete.";\r\n".'?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);
//create user password
$ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/login.php', "w");
$user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);

}
?><?php
    function edit_user_keep_password($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word,$toeditUser_can_upload,$toeditUser_can_delete){
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/users/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".$toCreateUserPassword."'".';'."\r\n".'$userIsAdmin= false;'."\r\n".'$can_upload='.$toeditUser_can_upload.";\r\n".'$can_delete='.$toeditUser_can_delete.";\r\n".'?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);

}
?>