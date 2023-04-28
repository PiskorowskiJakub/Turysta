<?php GetWorkMarketInfo(); CheckProfitAdded();?>

<div class="content">
    <div class="headerTitle">
        <h1>Praca</h1>
    </div>

    <div class="mainBoxContent">
    <div class="circle-container"></div>

        <div class="title">Rynek</div>
        <hr>
        <div class="workContent">
            <div class="workDescription">
                <?php if(isset($_SESSION['test'])) echo $_SESSION['test']; ?>
                Na rynku turystycznym gracz może handlować różnymi produktami, takimi jak pamiątki, jedzenie czy napoje. Wymaga to od niego umiejętności negocjacji, strategii i znajomości lokalnych zwyczajów i cen. Praca na rynku umożliwia graczowi zarabianie pieniędzy poprzez sprzedaż produktów turystom. Może w każdym momencie przerwać sprzedaż, dzięki czemu zbierze dochód za tyle czasu ile spędził przy stanowisku.
            <div class="workEarning">
                <div>
                    <form method="POST"><input type="submit" id="startWorkMarket" name="startWorkMarket" value="Rozpocznij" class="mainButton" onclick="startProgress()"/>
                    <input type="submit" id="endWorkMarket" name="endWorkMarket" value="Zakończ" class="mainButton"/></form>
                </div>
                <div class="timeLevelEarning">
                    <hr>
                    Czas: <b> <?php if(isset($_SESSION['maxWorkMarketDuration'])) echo round((($_SESSION['maxWorkMarketDuration'])/(60*60)),2) ?> godzin </b>
                    <hr>
                    Zarobek: <b id="earning"> </b>
                    <hr>
                </div>
            </div>
            <div class="progress-bar1">
                <?php if(strtotime($_SESSION['workMarketDate']) > time())
                    echo "Koniec pracy: <b>", date("Y-m-d H:i", strtotime($_SESSION['workMarketDate'])), "</b>";
                ?>
                <div class="prog striped animated green">
                    <div class="progBar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" id="myBar">
                        <span class="srOnly"></span>
                    </div>
                </div>
            </div>
            </div>

            
            <div class="imageWork">
                <img src="./img/work/empty-market.png"/>
            </div>
        </div>

    </div>
</div>
<?php
if(!(isset($_SESSION['skillLvl']))) GetSkillLvl();

CheckWorkMarketDate();
CalculateProfitMarket();

 

if(isset($_POST["startWorkMarket"])){
    WorkMarket();
    echo "<script> startProgress();</script>";
    echo "<script>document.getElementById('startWorkMarket').style.display = 'none'; </script>";
    echo "<script>document.getElementById('endWorkMarket').style.display = 'block'; </script>";
    if(isset($_SESSION['profit']))
        echo "<script>document.getElementById('earning').innerHTML = ". $_SESSION['profit']."</script>";
}


//$_SESSION['test'] = $_SESSION['profit'];

if(isset($_POST["endWorkMarket"])){
    CalculateProfitMarket();
    if(EndWorkMarket()){
        CheckWorkMarketDate();
        echo "<script> startProgress();</script>";
        echo "<script> window.location.reload();</script>";
    }
}



?>
