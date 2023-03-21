<?php 
if(isset($_GET["page"])) $strona = "./pages/".$_GET["page"];
else $strona = "./pages/profile";
?>
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

    <div class="pageLogin">

      

      <div class="top">
        <div class="infoBox">
          <div class="info">
            Gra jest w wersji Alpha. Po wejściu w wersję Beta, wszystkie statystyki zostaną zresetowane.
          </div>
        </div>
      </div>

      <div class="mainBox">
        <div class="topMain"><pre> </pre></div>
          
        <div class="main">
          <div class="content">    

                <?php include("./pages/login.php"); ?>

          </div>
        </div>

        <div class="bottomMain"><pre> </pre></div>
      </div>


    

        <div class="footer">
            <h3>© copyright Turysta-Game</h3>
            <h4>Godzina: <?php echo date("h:i"); ?></h4>
            <h4>Data: <?php echo date("Y-m-d"); ?></h4>
        </div>
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

<?php
    if(isset($_POST['wybor'])){
        if($_POST['wybor'] == 'm') {header('Location: ./main.php'); exit;}
    }
?>