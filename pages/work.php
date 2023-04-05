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
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum lectus metus, viverra non erat et, condimentum pulvinar nulla. Curabitur vitae nibh convallis, ultrices nisi auctor, lacinia nulla. Vestibulum id sapien rutrum, sagittis dolor ac, blandit risus. Nunc rutrum vehicula augue, vitae dignissim mauris iaculis at. Duis aliquet dictum metus sodales facilisis.
        <div class="workEarning">
            <div>
                <form method="POST"><input type="submit" name="signin" value="Rozpocznij" class="mainButton" onclick="startProgress()"/></form>
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

if(isset($_POST["signin"])){
    WorkMarket();
    echo "<script> startProgress()</script>";
}


?>