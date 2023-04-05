<?php
include('querys.php');
include('workScript.php');
include('database.php');

// Check Permission for user
function CheckUserPermission($permission){
    if(isset($_SESSION["userStatusGroup"]))
        if($_SESSION["userStatusGroup"] == $permission)
            return true;
        else return false;
    else return false;
}

// Check user Status Account
function CheckUserStatus($userStatus){
    if(isset($_SESSION["userStatusAccount"]))
        if($_SESSION["userStatusAccount"] == $userStatus)
            return true;
        else return false;
    else return false;
}


//----------------------------------------------------------------------
// Script to work market
//----------------------------------------------------------------------

function WorkMarket(){
    $typeOfEarning = 1;
    $data = true;
    $conn = ConnectDB();
    //SetWorkMarketData($conn, $_SESSION['userId'], $typeOfEarning);
          
    // Create input to display time
    echo '<input type="hidden" id="max_date" value="' . $_SESSION['workMarketDuration'] . '">';

}



?>