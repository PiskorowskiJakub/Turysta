<?php
include('database.php');

// -----------------------
// Jeżeli użytkownik nie kliknie "wyloguj " to aby wylogować uzytkownika po jakims czasie
// ustawić tymaczosowa sesje () session[time] ustawic czas odswiezenia strony i po godzinie niektywnosci usunac sesje
// jezeli od czasu ostatniego odswiezenia minela wiecej niz godzina to usun sesje w przeciwnym razie ustaw nowy czas
// ------------------------

// Creating a new user
function SignIn(){
    unset($_SESSION["errorCreate"]); 
    if(isset($_POST['signin']))
    {
        if(ValidateFieldReg($_POST['regUsername'], $_POST['regEmail'], $_POST['regPassword'], $_POST['regRepassword']))
        {
            $username = $_POST['regUsername'];
            $email = $_POST['regEmail'];
            $password = password_hash($_POST['regPassword'], PASSWORD_DEFAULT);
            $invCode = $_POST['RegInvCode'];
            $conn = ConnectDB();

            if(SignInUser($conn, $username, $email, $password, $invCode) == true){
                $_SESSION["errorCreate"] = "Poprawnie stworzono konto. Możesz się zalogować";
                header("Location: ./index.php"); exit;
            }
            else
            {
                $_SESSION["errorCreate"] = "Blad w tworzeniu konta";
                header("Location: ./index.php"); exit;
            }

        }
    }
}

// Validation of registration form fields
function ValidateFieldReg($username, $email, $password, $rePassword){
    if(strlen($username) != 0){
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if($password == $rePassword){
                return true;
            }
            else return false;
        }
        else return false;
    }
    else return false;
}




?>