<?php

function CalculateProfitMarket(){
    include('startupValue.php');
    
    $startupMoneyWorkMarket;
    $_SESSION['maxProfit'] = ($startupMoneyWorkMarket * $_SESSION['skillLvl'])* $_SESSION['workMarketEarningFactor'];

    $profitPerSecond = $_SESSION['maxProfit'] / $_SESSION['maxWorkMarketDuration'];
    if(isset($_SESSION['elapsedTime']))
        $_SESSION['profit'] = round(($profitPerSecond * $_SESSION['elapsedTime']), 2);
}


?>