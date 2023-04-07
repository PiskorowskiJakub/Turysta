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
    
    if(strtotime($_SESSION['workMarketDate']) > time()){
        CheckTimeWorkMarketData();  // Calculation of the remaining time
    }
    else{
        // Set the end of work time and save it do database
        SetWorkMarketData($conn, $_SESSION['userId'], $typeOfEarning); 
    }
        

    // Create input to display time
    echo '<input type="hidden" id="max_date" value="' . $_SESSION['workMarketDuration'] . '">';

}

// Check remaining time and display the button start or end
function CheckWorkMarketDate(){
    if(strtotime($_SESSION['workMarketDate']) > time()){
        WorkMarket();
        echo "<script> startProgress()</script>";
        echo "<script>document.getElementById('startWorkMarket').style.display = 'none'; </script>";
        echo "<script>document.getElementById('endWorkMarket').style.display = 'block'; </script>";
    }
    else
    {
        echo "<script>document.getElementById('startWorkMarket').style.display = 'block'; </script>";
        echo "<script>document.getElementById('endWorkMarket').style.display = 'none'; </script>";
    }
}

function EndWorkMarket(){
    $typeOfEarning = 1;
    $currentData = date('Y-m-d H:i:s');
    $conn = ConnectDB();

    if(strtotime($_SESSION['workMarketDate']) > time()){
        global $sqlUpdateDateWorkMarketLog;
        if($resultUpdateDateWorkMarketLog = ExecuteQuery($conn, 'e', $sqlUpdateDateWorkMarketLog, 's', $currentData, 'i', $_SESSION['userId'], 'i', $typeOfEarning))
            return true;
        else return false;
    }
}



?>