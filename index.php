<?php
    
    $debug = false;
    installifneeded();
    $secret_word = "word";
    $ecad_php_version ="ECAD PHP fileviewer v0.1.08";
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
        
        if(file_exists($datarootpath."/".$c_username)){
            include $datarootpath."/".$c_username."/userconfig.php";
            include $datarootpath."/".$c_username.'/login.php';
            //if ((in_array($_COOKIE['ECAD_PHP_fileviewer_login'], $acceptableuserLoginCockies))){//$userpasswordHash == $cookie_hash)) {
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
            //from server
            $str3706849=file_get_contents($datarootpath."/".$c_username.'/login.php');

            //replace something in the file string - this is a VERY simple example
            $str3706849=str_replace('<?php $acceptableuserLoginCockies = $acceptableuserLoginCockies."'.$_COOKIE['ECAD_PHP_fileviewer_login'].'-"; ?>', '',$str3706849);

            //write the entire string
            file_put_contents($datarootpath."/".$c_username.'/login.php', $str3706849);

            //from client
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
                rrmdir($datarootpath."/".$_POST['user_to_delete']);
            }
            
            if ( isset( $_POST['create_user'] ) ) {
                $show_user_interface = false;
                echo'</br><form method="POST" action="">Username: <input type="text" name="username"></input><br/>Password: <input type="text" name="password"></input><br/>is admin:<input type="checkbox" name="is_admin" value="is admin"><br/><input name="create_user_submit" value="OK" type="submit"></form>';
            }
            if ( isset( $_POST['edit_user'] ) ) {
                $show_user_interface = false;
                echo'</br><form method="POST" action="">Username: <input type="text" name="username" value="'.$_POST['user_to_delete'].'" readonly></input><br/>Password: <input type="text" name="password">(left empty to keep password)</input><br/>is admin:<input type="checkbox" name="is_admin" value="is admin"><br/><input name="edit_user_submit" value="OK" type="submit"></form>';
            }
            if ( isset( $_POST['create_user_submit'] ) ) {
                if($_POST['username'] != ""){
                create_user($_POST['username'],$_POST['password'],$datarootpath,$secret_word);
                }
            }
            if ( isset( $_POST['edit_user_submit'] ) ) {
                if($_POST['username'] != "" && file_exists($datarootpath."/".$_POST['username'])){
                    
                    if ($_POST['password'] ==""){
                        $current_administrative_user = $user;
                        include $datarootpath."/".$_POST['username']."/userconfig.php";
                        edit_user_keep_password($_POST['username'],$userpasswordHash,$datarootpath,$secret_word);
                        
                        include $datarootpath."/".$current_administrative_user."/userconfig.php";
                    }else{
                
                edit_user($_POST['username'],$_POST['password'],$datarootpath,$secret_word);
                    }
                }
            }
            //--------------------
            //output
            if($show_user_interface){
                echo '</br>users:</br>';
                
                $files = scandir($datarootpath.'/');
                sort($files); // this does the sorting
                
                $datein = 0;
                $nichtgelisteteDatein = array("index.php", ".htaccess", ".", "..");
                foreach($files as $file){
                    if (in_array ( $file , $nichtgelisteteDatein )){
                    }else{
                        if ($file == "admin"){
                        echo'<form method="POST" action="">'.$file.'<span style="padding-left:80px"></span>   <input type="hidden" name="user_to_delete" value="'.$file.'"><input name="edit_user" value="edit" type="submit"></form>';
                        }else{
                        echo'<form method="POST" action="">'.$file.'<span style="padding-left:80px"></span>   <input type="hidden" name="user_to_delete" value="'.$file.'"><input name="edit_user" value="edit" type="submit"><input name="delete_user" value="delete" type="submit"></form>';
                        }
                    }
                }
                echo'</br><form method="POST" action=""><input name="create_user" value="new user" type="submit"></form>';
            }
           //---------------------------------------------
        }else{

    
    //start ecad file view------------
    //session_start();
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
    
    //needet data for components (old static variables

    //$user = "user1";
    //$userpath = "/user1";
    //$datarootpath = "C:/ECAD PHP fileviewer X data";
        
        //get path
        $path = $_GET["path"];
    $path = str_replace ("%20" , " " , $path);
        //load configs
        
        //------------
    $fullpath = $datarootpath.$userpath."/data".$path;
    //validate path
    if (strlen($path) >0){
        if ($path[0] != "/"){
            $path ="/".$path;
        }
    }
    $fullpath = $datarootpath.$userpath."/data".$path;
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
        $fullpath = $datarootpath.$userpath."/data".$path;
    }
    //redo fullpath for file reading
    
    if($debug){
        echo "</br>--------------debug------------------";
        echo "</br> path: ".$path;
        echo "</br> fullpath: ".$fullpath;
        echo "</br>-------------------------------------";
        echo "</br></br></br></br>";
    }
    //echo getcwd();
    if(is_file($fullpath)){
        if($debug){
            echo "</br>--------------INFO-------------------";
            echo "</br>Ist eine datei";
            echo "</br>-------------------------------------";
            echo "</br>";
        }
        //do download
        //$path = ltrim($path, '/');
        //$path = rawurlencode($path);
        $filename = substr($path, strrpos($path, '/') + 1);
        
        substr($path, strrpos($path, '/') + 1);
        
        makeDownload($fullpath, filetype($fullpath),$filename);

        
        //read file for download (old downloader)
        //$my_download_file = fopen($fullpath, "r") or die();
        //echo fread($my_download_file,filesize($fullpath));
        //fclose($my_download_file);
        
        //read file for download new with buffer
        /*
        $handle = fopen($fullpath, "r") or die("Couldn't get handle");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                echo $buffer;
                // Process buffer here..
            }
            fclose($handle);
        }
         */
        ob_end_clean();
        readfile($fullpath);
        //---------
        
        
    }else{
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        echo '<html xmlns="http://www.w3.org/1999/xhtml">';
        echo '<head>';
        echo '<title>'.$ecad_php_version.'</title>';
        echo '</head>';
        echo '<body>';

        echo $ecad_php_version.'    <a href="index.php?action=logout"> logout </a></br>';
        echo "user: ".$user."</br>";
        
        //neues path anzeige system

        if ($path == '/'){
            echo "</br> path: ";
            echo'<a href="'.'">root</a><a> /</a>';
        }else{
            $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?path=/";
            
            echo "</br> path: ";
            $path_array = split('/',$path);
            
            echo'<a href="'.$newpath.'">root</a><a> /</a>';
            //echo count($path_array);
            for ($path_part = 1; $path_part <= (count($path_array)-2); $path_part++) {
                $newpath = $newpath.$path_array[$path_part].'/';
                if ($path_part ==(count($path_array)-2)){
                    echo '<a> </a>'.'<a href="'.$newpath.'">'.$path_array[$path_part].'</a><a> /</a>';
                }else{
                    echo '<a> </a>'.'<a href="'.$newpath.'">'.$path_array[$path_part].'</a><a> /</a>';
                }
                
                
                //echo $path_array[$path_part]."   ";
            }
        }
        echo'</br></br>';
        
        
        //altes path anzeige system
        //echo "</br> path: ".$path ."</br>";
        
        if(file_exists($fullpath.'/')){
            
            $nichtgelisteteDatein = array("index.php", ".htaccess", ".", "..");
            
            $files = scandir($fullpath.'/');
            sort($files); // this does the sorting
            
            $datein = 0;
            foreach($files as $file){
                
                
                if (in_array ( $file , $nichtgelisteteDatein )){
                    
                    //echo'<a href="http://www.epiccad.at/privatedownloads/'.$file.'">'.$file.'</a> </br>';
                }else{
                    $datein++;
                    
                    //---------------------------------
                    if(is_file($fullpath.'/'.$file))
                    {
                        if(round((filesize($fullpath.'/'.$file)/1000.000),3)>1000.000){
                            echo round((filesize($fullpath.'/'.$file)/1000.000/1000),3)."MB   ";
                        }else{
                        echo round((filesize($fullpath.'/'.$file)/1000.000),3)."kb   ";
                        }
                        //echo (filesize($fullpath.'/'.$file)/1024.000)."kb   ";
                        //echo ("datei   ");
                    }
                    else
                    {
                        echo ("Folder   ");
                    }
                    //--
                    //-------test
                    
                    //new
                    $newpath = substr(curPageURL(), 0, strpos(curPageURL(),basename(__FILE__))).basename(__FILE__)."?path=".$path;

                    echo'<a href="'.$newpath.$file.'">'.$file."       ".'</a> </br>';
                    //old
              //      $newpath = curPageURL();
              //      if($newpath[strlen($newpath) - 1] != "/"){
              //          echo'<a href="'.curPageURL().'/'.$file.'">'.$file."       ".'</a> </br>';
              //      }else{
              //          echo'<a href="'.curPageURL().''.$file.'">'.$file."       ".'</a> </br>';
              //      }
                   
                    
                }
                
                
            }
            echo "</br>";
            if($datein == 1){
                echo $datein." Object";
                //echo "Keine Datein";
            }else{
                echo $datein." Objects";
            }
            
            
        }else{
            
            echo "</br> Folder not found";
            
        }
        echo "</body>";
        echo "</html>";
    }
    
        

        //end ecad file view------------------------
        //authentification service login
    }
        }else{
            $user = $_POST['user'];
            $pass = $_POST['pass'];
            
            //only admin login
            /*
            if($user == "admin"&& $pass == "admin")
            {
                
                setcookie('ECAD_PHP_fileviewer_login',$user.','.md5($pass.$secret_word));
                echo "you are loged in      please wait.......";
                header("Refresh:0; url=index.php?path=/");
                
            }
             */
            $loginaccepted = false;
            if(file_exists($datarootpath."/".$user)){
                include $datarootpath."/".$user."/userconfig.php";
                if(md5($pass.$secret_word) == $userpasswordHash){
                                $loginaccepted = true;
                }
            }
            
            if($loginaccepted)
            {
                $newUserCockies = $user.','.md5($pass.$secret_word.time());
                //activate cockie
                //$ecad_php_user_config_file = fopen($datarootpath."/".$user.'/login.php', "w");
                $user_config_file_Standard = '<?php $acceptableuserLoginCockies = $acceptableuserLoginCockies."'.$newUserCockies.'-"; ?>';
                file_put_contents($datarootpath."/".$user.'/login.php', $user_config_file_Standard, FILE_APPEND);
                //fwrite($ecad_php_user_config_file, $user_config_file_Standard);
                //fclose($ecad_php_user_config_file);
                
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
function installifneeded() {
        $secret_word = "word";
    if(!file_exists("config.php")){
        //ecad php config file
        $ecadphpconfigfile = fopen("config.php", "w");
        $ecadphpconfigStandard = '<?php'."\r\n".'$datarootpath='."'".__DIR__.'/ECAD PHP fileviewer X data'."'".';'."\r\n".'$adminPassword="admin"'."\r\n".'?>'.'<?php'."\r\n".'$user='.'"user0";'."\r\n".'$userpath='.'"/user0";'."\r\n".'?>';
fwrite($ecadphpconfigfile, $ecadphpconfigStandard);
fclose($ecadphpconfigfile);
//ecad php data folder
//mkdir("ECAD PHP fileviewer X data");
mkdir('./ECAD PHP fileviewer X data/user0/data/test', 0777, true);
mkdir('./ECAD PHP fileviewer X data/user0/downloadpreperation', 0777, true);

//create user0
$ecad_php_user_config_file = fopen('./ECAD PHP fileviewer X data/user0/userconfig.php', "w");
$user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5("admin".$secret_word)."'".';'."\r\n".'?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);
//create user0 password
$ecad_php_user_config_file = fopen('./ECAD PHP fileviewer X data/user0/login.php', "w");
$user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);

//create admin
mkdir('./ECAD PHP fileviewer X data/admin');
$ecad_php_user_config_file = fopen('./ECAD PHP fileviewer X data/admin/userconfig.php', "w");
$user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5("admin".$secret_word)."'".';'."\r\n".'$userIsAdmin= true'."\r\n".'?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);
//create admin password
$ecad_php_user_config_file = fopen('./ECAD PHP fileviewer X data/admin/login.php', "w");
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
    function create_user($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word){
                            //create user
                            mkdir($ECAD_PHP_fileviewer_X_data_folder.'/'.$toCreateUsername);
                            mkdir($ECAD_PHP_fileviewer_X_data_folder.'/'.$toCreateUsername.'/data');
                            mkdir($ECAD_PHP_fileviewer_X_data_folder.'/'.$toCreateUsername.'/downloadpreperation');
                            $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/'.$toCreateUsername.'/userconfig.php', "w");
                            $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5($toCreateUserPassword.$secret_word)."'".';'."\r\n".'$userIsAdmin= false'."\r\n".'?>';
                            fwrite($ecad_php_user_config_file, $user_config_file_Standard);
                            fclose($ecad_php_user_config_file);
                            //create user password
                            $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/'.$toCreateUsername.'/login.php', "w");
                            $user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
                            fwrite($ecad_php_user_config_file, $user_config_file_Standard);
                            fclose($ecad_php_user_config_file);
                            
                            
                            
                            
                            
                            
    }
?><?php
    function edit_user($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word){
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".md5($toCreateUserPassword.$secret_word)."'".';'."\r\n".'$userIsAdmin= false'."\r\n".'?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);
//create user password
$ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/'.$toCreateUsername.'/login.php', "w");
$user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);






}
?><?php
    function edit_user_keep_password($toCreateUsername,$toCreateUserPassword,$ECAD_PHP_fileviewer_X_data_folder,$secret_word){
        //create user
        $ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/'.$toCreateUsername.'/userconfig.php', "w");
        $user_config_file_Standard = '<?php'."\r\n".'$userpasswordHash='."'".$toCreateUserPassword."'".';'."\r\n".'$userIsAdmin= false'."\r\n".'?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);
//create user password
$ecad_php_user_config_file = fopen($ECAD_PHP_fileviewer_X_data_folder.'/'.$toCreateUsername.'/login.php', "w");
$user_config_file_Standard = '<?php $acceptableuserLoginCockies = "-"; ?>';
fwrite($ecad_php_user_config_file, $user_config_file_Standard);
fclose($ecad_php_user_config_file);






}
?>