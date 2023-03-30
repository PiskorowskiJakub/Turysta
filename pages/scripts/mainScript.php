<?php
include('querys.php');

// Check Permission for user
function CheckUserPermission($permission){
    if(isset($_SESSION["userStatusGroup"]))
        if($_SESSION["userStatusGroup"] == $permission)
            return true;
        else return false;
    else return false;
}

function CheckUserStatus($userStatus){
    if(isset($_SESSION["userStatusAccount"]))
        if($_SESSION["userStatusAccount"] == $userStatus)
            return true;
        else return false;
    else return false;
}


?>