<?php 
include('./scripts/database.php');
?>
<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="../styles/menu.css">

  </head>
  <body>

    <div class="pageLogin">

      <div class="mainBox">
        <div class="topMain"><pre> </pre></div>
          
        <div class="main">
          <div class="content">    

                <h1> Wystąpił bład</h1>

                <?php 
                    //$error = ChceckError();
                    if (isset($_GET['error'])) {
                    $error = $_GET['error'];
                    echo $error;
                    }
                    else echo "nie znaleziono błędu";
                ?>

                <hr>
                <h3>Zrób screena i wyślij do Administratora strony.</h3>
                <p>Adres: </p>
                <p><a href="../index.php"><input type="button" name="powrot" value="Powrót do strony głównej" class="mainButton"/></a></p>

          </div>
        </div>

        <div class="bottomMain"><pre> </pre></div>
      </div>


    

        <div class="footer">
            <h3>© copyright Jakub Piskorowski</h3>
            <p>Godzina: <?php echo date("H:i"); ?></p>
            <p>Data: <?php echo date("Y-m-d"); ?></p>
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
        //else if($_POST['wybor'] == 'l') include("./pages/login.php");
    }
?>