<?php include('scripts/signin.php'); 
?>

<div class="loginBox">
    <h1>Zaloguj się </h1>
    <div class="errorInfo"><?php if(isset($_SESSION["errorLogin"])) {echo $_SESSION["errorLogin"]; unset($_SESSION["errorLogin"]); } ?></div>

    <form method="POST">
        <label for="fname">Email: </label>
        <input type="email" name="email" required/> </br>
        <label for="fname">Hasło: </label>
        <input type="password" name="password" required/> </br>
        <input type="submit" name="login" value="Zaloguj się" class="mainButton"/>
    </form>
</div>
<div class="signinBox">
    <h1>Zarejestruj się </h1>
    <div class="errorInfo"><?php if(isset($_SESSION["errorCreate"])) {echo $_SESSION["errorCreate"]; unset($_SESSION["errorCreate"]); } ?></div>

    <form method="POST">
        <label for="fname">Nazwa postaci: </label>
        <input type="text" name="regUsername" required/> <span class="required"></span></br>
        <label for="fname">Email: </label>
        <input type="email" name="regEmail" required/> <span class="required"></span></br>
        <label for="fname">Hasło: </label>
        <input type="password" name="regPassword" required/> <span class="required"></span></br>
        <label for="fname">Powtórz hasło: </label>
        <input type="password" name="regRepassword" required/> <span class="required"></span></br>
        <label for="fname">Kod polecającego: </label>
        <input type="text" name="RegInvCode" /> </br>
        <input type="submit" name="signin" value="Zarejestruj się" class="mainButton"/>
    </form>

    </br><span class="required"></span> Pola wymagane </br>
    
    </div>
    <div class="screens">
        <h1>Mockupy</h1>
        <div class="img">
            <a href="./img/Profil.png" target="_blank">
                <img src="./img/Profil.png" title="Profil gracza"/>
            </a>
            <a href="./img/Praca-progress.png" target="_blank">
                <img src="./img/Praca-progress.png" title="Praca"/>
            </a>
            <a href="./img/Umiejetnosci-aktywne-1.png" target="_blank">
                <img src="./img/Umiejetnosci-aktywne-1.png" title="Umiejetnosci"/>
            </a>
            <a href="./img/Odznaki.png" target="_blank">
                <img src="./img/odznaki.png" title="Odznaki"/>
            </a>
        </div>
    </div>

<?php
    if(isset($_POST['signin'])) SignIn();
    if(isset($_POST['login'])) LoginUser();

?>
