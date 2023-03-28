<?php 
session_start();
include('pages/scripts/mainScript.php');

// Check user Permission
if(!(CheckUserPermission("Uzytkownik") || CheckUserPermission("Administrator") || CheckUserPermission("Moderator") || CheckUserPermission("Gosc") || CheckUserPermission("VIP") || CheckUserPermission("Partner"))){
  header('Location: ./index.php'); exit;
}

// Switch pages
if(isset($_GET["page"])) $strona = "./pages/".$_GET["page"];
else $strona = "./pages/profile";
?>

<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!--https://www.w3schools.com/icons/icons_reference.asp -->

    <!-- Styles -->
    <link rel="stylesheet" href="./styles/main.css">
    <link rel="stylesheet" href="./styles/menu.css">
    <link rel="stylesheet" href="./styles/content.css">

  </head>
  <body>

    <div class="page">

      <div class="menuBox">
        <div class="topnav" id="myTopnav">
          <a href="main.php?page=profile" class="active"></a>
          <a href="main.php?page=profile"><i class="fa fa-address-card"></i> Profil</a>
          <a href="main.php?page=skills"><i class="fa fa-flask"></i> Umiejetnosci</a>
          <a href="main.php?page=work"><i class="fa fa-suitcase"></i> Praca</a>
          <a href="main.php?page=badges"><i class="fa fa-address-card"></i> Odznaki</a>
          <a href="index.php?logout=true"><i class="fa fa-sign-out"></i> Wyloguj się</a>
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
            <a title="Nazwa"><i class="fa fa-address-card"></i> <?php if(isset($_SESSION["userName"])) echo $_SESSION["userName"]; ?> </a><br>
            <a title="Monety"><i class="fa fa-money"></i> <?php if(isset($_SESSION["userMoney"])) echo $_SESSION["userMoney"]; ?> </a><br>
            <a title="Bilety"><i class="fa fa-file-text"></i> <?php if(isset($_SESSION["userTicket"])) echo $_SESSION["userTicket"]; ?> </a><br>
            <a title="Punkty"><i class="fa fa-bar-chart"></i> <?php if(isset($_SESSION["userPoints"])) echo $_SESSION["userPoints"]; ?></a>
          </div>
        </div>
      </div>

      <div class="mainBox">
        <div class="topMain"><pre> </pre></div>
          
        <div class="main">
          <div class="content">
            
            <?php if(($strona)!=null) include($strona.".php"); ?>

          </div>
        </div>

        <div class="bottomMain"><pre> </pre></div>
      </div>


    

    <div class="footer"><h3>© copyright Jakub Piskorowski</h3>
      <p>Godzina: <?php echo date("H:i"); ?></p>
      <p>Data: <?php echo date("Y-m-d"); ?></p>
    </div>
    </div>
  </body>
</html>

<script type="text/javascript">
function myFunction() {
  var x = document.getElementById("myTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}


</script>

