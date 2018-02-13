<?php
    //the file will be deleted after the update is complete
    function execute_update_parameter($dataFolderName,$ecad_php_version_id){
        //------------ setting a new password in the updated password system --------------
        $username = 'admin';
        $password = 'admin';
        edit_user($dataFolderName, $username, $password, "false", "", "false", "false", "", "false", "false", "0","false","false","false");
        //---------------------------------------------------------------------------------


        
        
    }
?>
