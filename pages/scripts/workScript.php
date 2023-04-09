<?php
include('economy.php');

// Get all work data info and save to session 
function GetWorkMarketInfo(){
     try{
     include('startupValue.php');

     $conn = ConnectDB();
     $idWork = 1; // 1 - Market
        global $sqlGetWorkMarketInfo;
        $resultGetWorkMarketInfo = ExecuteQuery($conn, 's', $sqlGetWorkMarketInfo, 'i', $idWork, 'i', $_SESSION['userId']);
        while($row = $resultGetWorkMarketInfo->fetch_array(MYSQLI_NUM)){
            $_SESSION['workMarketName'] = $row[0];
            $_SESSION['workMarketDuration'] = $row[1];
            $_SESSION['workMarketEarningFactor'] = $row[2];
            $_SESSION['workMarketDate'] = $row[3];
            $_SESSION['workMarketEarning'] = $row[4];
        }
        $_SESSION['maxWorkMarketDuration'] = $_SESSION['workMarketDuration'];
     }catch(Exception $e){
          $erro = "error: Praca na rynku, pobranie danych startowych: " . $e->getMessage();
          ChceckError($erro);
      }
}

// Set the end of work time and save it do database
function SetWorkMarketData($conn, $userId, $typeOfEarning){
     include('startupValue.php');

     $currentData = date('Y-m-d H:i:s');

     $dataEndOfEarning = date('Y-m-d H:i:s', time() + $_SESSION['workMarketDuration']);
     global $sqlUpdateWorkMarketLog;
     if($resultUpdateWorkMarketLog = ExecuteQuery($conn, 'e', $sqlUpdateWorkMarketLog, 's', $dataEndOfEarning, 'd', $_SESSION['maxProfit'], 'i', $_SESSION['userId'], 'i', $typeOfEarning))
          return true;
     else return false;
}

// Calculation of the remaining time
function CheckTimeWorkMarketData(){
     $endDate = strtotime($_SESSION['workMarketDate']);
     $currentDate = time(); 
     $difference = $endDate - $currentDate; 
     $_SESSION['workMarketDuration'] = $difference;

     $maxSecond = $_SESSION['maxWorkMarketDuration'];
     $_SESSION['elapsedTime'] = $maxSecond- $_SESSION['workMarketDuration']; 
}

function GetSkillLvl(){
     $typeOfSkill = 1; // 1 - trade skill 
     $conn = ConnectDB();
     global $sqlGetLvlSkill;
     $resultGetLvlSkill = ExecuteQuery($conn, 's', $sqlGetLvlSkill, 'i', $typeOfSkill, 'i', $_SESSION['userId']);
     while($row = $resultGetLvlSkill->fetch_array(MYSQLI_NUM)){
          $_SESSION['skillLvl'] = $row[0];
     }
     if($_SESSION['skillLvl'] == 0) $_SESSION['skillLvl'] = 0.5;
}


?>