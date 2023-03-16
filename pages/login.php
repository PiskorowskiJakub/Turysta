<?php include('scripts/signin.php'); ?>

<div class="loginBox">
    <h1>Zaloguj się </h1>

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

    <form method="POST">
        <label for="fname">Nazwa postaci: </label>
        <input type="text" name="regUsername" required/> <span class="required"></span></br>
        <label for="fname">Email: </label>
        <input type="email" name="regEmail" required/> <span class="required"></span></br>
        <label for="fname">Hasło: </label>
        <input type="password" name="RegPassword" required/> <span class="required"></span></br>
        <label for="fname">Powtórz hasło: </label>
        <input type="password" name="RegRepassword" required/> <span class="required"></span></br>
        <label for="fname">Kod polecającego: </label>
        <input type="text" name="RegInvCode" /> </br>
        <input type="submit" name="signin" value="Zarejestruj się" class="mainButton"/>
    </form>

    </br><span class="required"></span> Pola wymagane
</div>

<?php
    if(isset($_POST['signin'])) SignIn();
?>
