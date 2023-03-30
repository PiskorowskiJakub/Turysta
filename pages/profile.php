<div class="headerTitle">
    <h1>Profil</h1>
</div>

<div class="mainBoxContent">
<div class="circle-container"></div>

    <div class="mainStats">
        <div class="stats">
            Nazwa: <b><?php if(isset($_SESSION["userName"])) echo $_SESSION["userName"]; ?></b> </br>
            Świat: <b><?php if(isset($_SESSION["userWorld"])) echo $_SESSION["userWorld"]; ?></b> </br>
            Rozdział: <b><?php if(isset($_SESSION["userChapter"])) echo $_SESSION["userChapter"]; ?></b> </br>
        </div>
        <div class="imageProfile">
            <img src="img/layout/Female-Avatar-3.png"/>
        </div>
    </div>

    <hr class="hrProfile">
    <div class="mainStats">
        <div class="stats">
            Punkty: <b><?php if(isset($_SESSION["userPoints"])) echo $_SESSION["userPoints"]; ?> </b> </br>
            Monety: <b><?php if(isset($_SESSION["userMoney"])) echo $_SESSION["userMoney"]; ?> </b></br>
            Bilety: <b><?php if(isset($_SESSION["userTicket"])) echo $_SESSION["userTicket"]; ?> </b></br>
        </div>
    </div>

</div>

<div class="headerTitle">
    <h1>Ranking</h1>
</div>

<div class="mainBoxContent">
<div class="circle-container"></div>

    <div class="stats">
        Punktów: <b>...</b> </br>
        Monet: <b>...</b> </br>
    </div>
</div>