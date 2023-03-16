<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="./styles/menu.css">

  </head>
  <body>

    <div class="page">

      


      <div class="menuBox">
        <div class="topnav" id="myTopnav">
          <a href="#home" class="active"></a>
          <a href="#profil"><i class="fa fa-address-card"></i> Profil</a>
          <a href="#skills"><i class="fa fa-flask"></i> Umiejetnosci</a>
          <a href="#work"><i class="fa fa-suitcase"></i> Praca</a>
          <a href="#badges"><i class="fa fa-address-card"></i> Odznaki</a>
          <a href="#logout"><i class="fa fa-sign-out"></i> Wyloguj się</a>
          <a href="javascript:void(0);" class="icon" onclick="myFunction()">
            <i class="fa fa-bars"></i>
          </a>
        </div>
      </div>

      <div class="top">
        <div class="infoBox">
          <div class="info">
            Aż 90% osób ukończyło minimum szkołę średnią
          </div>
        </div>
        <div class="profilBox">
          <div class="profilImage">
              <img src="img/layout/Female-Avatar-3.png"/>
          </div>
          <div class="profil"> 
            <a title="Monety"><i class="fa fa-money"></i> 123.53 </a><br>
            <a title="Bilety"><i class="fa fa-file-text"></i> 16 </a><br>
            <a title="Punkty"><i class="fa fa-bar-chart"></i> 2.432.534</a>
          </div>
        </div>
      </div>

      <div class="mainBox">
        <div class="topMain"><pre> </pre></div>
          
        <div class="main">
          <div class="content">
            
            <?php include("./pages/profile.php") ?>

          </div>
        </div>

        <div class="bottomMain"><pre> </pre></div>
      </div>


    

    <div class="footer"><h3>© copyright Turysta-Game</h3></div>
    </div>
  </body>
</html>

<script>
function myFunction() {
  var x = document.getElementById("myTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}
</script>