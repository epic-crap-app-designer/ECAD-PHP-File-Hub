<?php

    
    if ($ecad_php_version_id< 135){
        
        if(isset($_POST['acceptUpdateInstaller135'])){
            //update has been confirmaed
            
            
            //delete update.php
            unlink('update.php');
        }else{
            echo 'An update has been downloaded.</br>Please press the ok button to start the installation';
            echo '</br><form method="POST" action=""><input name="acceptUpdateInstaller135" value="ok" type="submit"></form><br/><br/>';
        }

    }else{
        //update already installed (delete update.php)
        unlink('update.php');
    }

   
?>
