<?php
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
     }catch(Exception $e){
          $erro = "error: Praca na rynku, pobranie danych startowych: " . $e->getMessage();
          ChceckError($erro);
      }
}

function SetWorkMarketData($conn, $userId, $typeOfEarning){
     include('startupValue.php');

     $currentData = date('Y-m-d H:i:s');

     $seconds = intval($_SESSION['workMarketDuration'] / 1000); // konwersja na sekundy
     $_SESSION["test"] = $_SESSION['workMarketDuration'];
     $dataEndOfEarning = date('Y-m-d H:i:s', time() + 180);

     //$dataEndOfEarning = $currentData + $workMarketDuration; 
     $profit = $startupMoneyWorkMarket * $_SESSION['workMarketEarningFactor'];

     global $sqlUpdateWorkMarketLog;
     if($resultUpdateWorkMarketLog = ExecuteQuery($conn, 'e', $sqlUpdateWorkMarketLog, 's', $dataEndOfEarning, 'd', $profit, 'i', $_SESSION['userId'], 'i', $typeOfEarning))
          return true;
     else return false;
}


?>