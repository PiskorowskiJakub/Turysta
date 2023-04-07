<?php GetWorkMarketInfo(); ?>


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
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum lectus metus, viverra non erat et, condimentum pulvinar nulla. Curabitur vitae nibh convallis, ultrices nisi auctor, lacinia nulla. Vestibulum id sapien rutrum, sagittis dolor ac, blandit risus. Nunc rutrum vehicula augue, vitae dignissim mauris iaculis at. Duis aliquet dictum metus sodales facilisis.
        <div class="workEarning">
            <div>
                <form method="POST"><input type="submit" id="startWorkMarket" name="startWorkMarket" value="Rozpocznij" class="mainButton" onclick="startProgress()"/>
                <input type="submit" id="endWorkMarket" name="endWorkMarket" value="Zakończ" class="mainButton"/></form>
            </div>
            <div class="timeLevelEarning">
                <hr>
                Czas: <b> 8 godzin </b>
                <hr>
                Zarobek: <b> 247 </b>
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
            <img src="img/work/empty-market.png"/>
        </div>
    </div>

</div>
<?php
CheckWorkMarketDate();

if(isset($_POST["startWorkMarket"])){
    WorkMarket();
    echo "<script> startProgress();</script>";
    echo "<script>document.getElementById('startWorkMarket').style.display = 'none'; </script>";
    echo "<script>document.getElementById('endWorkMarket').style.display = 'block'; </script>";
}

if(isset($_POST["endWorkMarket"])){
    if(EndWorkMarket()){
        $_SESSION['test'] = "true1";
        CheckWorkMarketDate();
        echo "<script> startProgress();</script>";
        echo "<script> window.location.reload();</script>";
    }
    else $_SESSION['test'] = "false1";
}



?>
