<?php
include('database.php');

// -----------------------
// Jeżeli użytkownik nie kliknie "wyloguj " to aby wylogować uzytkownika po jakims czasie
// ustawić tymaczosowa sesje () session[time] ustawic czas odswiezenia strony i po godzinie niektywnosci usunac sesje
// jezeli od czasu ostatniego odswiezenia minela wiecej niz godzina to usun sesje w przeciwnym razie ustaw nowy czas
// ------------------------

//----------------------------------------------------------------------
// Registration system
//----------------------------------------------------------------------

// Creating a new user
function SignIn(){
    unset($_SESSION["errorCreate"]); 
    if(isset($_POST['signin']))
    {
        if(ValidateFieldReg(htmlspecialchars($_POST['regUsername']), htmlspecialchars($_POST['regEmail']), htmlspecialchars($_POST['regPassword']), htmlspecialchars($_POST['regRepassword'])))
        {
            $username = htmlspecialchars($_POST['regUsername']);
            $email = htmlspecialchars($_POST['regEmail']);
            $password = htmlspecialchars(password_hash($_POST['regPassword'], PASSWORD_DEFAULT));
            $invCode = htmlspecialchars($_POST['RegInvCode']);
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
        else{
            if(!isset($_SESSION["errorCreate"])){
                $_SESSION["errorCreate"] = "Nie mozna manipulowac w wlasciwosciach strony. Nie ładnie tak -.-' ";
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
            else {
                $_SESSION["errorCreate"] = "Hasła nie są takie same";
                header("Location: ./index.php"); exit;
                return false;
            }
        }
        else return false;
    }
    else return false;
}

//----------------------------------------------------------------------
// Login system
//----------------------------------------------------------------------

function LoginUser(){

    if(isset($_POST['login']))
    {
        if(strlen((htmlspecialchars($_POST["email"])) != 0 && strlen(htmlspecialchars($_POST["password"])) != 0) && (filter_var(htmlspecialchars($_POST["email"]), FILTER_VALIDATE_EMAIL)))
        {
            $email = htmlspecialchars($_POST["email"]);
            $password = htmlspecialchars($_POST["password"]);
            $conn = ConnectDB();

            if(CheckIfRegistered($conn, $email, $password)){
                $_SESSION["userId"] = GetUserId($conn, $email);
                if(InsertUserLogin($conn, $_SESSION["userId"])){
                    GetUserData($conn, $_SESSION["userId"]); // save user data to session
                    header("Location: ./main.php"); exit;
                }
            }
            else{
                $_SESSION["errorLogin"] = "Taki uzytkownik nie istnieje ";
                header("Location: ./index.php"); exit;
            }
        }
        else{
            $_SESSION["errorLogin"] = "Nie mozna manipulowac w wlasciwosciach strony. Nie ładnie tak -.-' ";
            header("Location: ./index.php"); exit;
        }
    }
}



?>