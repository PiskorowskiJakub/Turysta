<?php


// Nawiazanie polaczenia z baza
function ConnectDB(){
    include('keys.php');
    try {
        $conn = mysqli_connect($hostname, $username, $password, $database);
      } catch (Exception $e) {
        $erro = "error: z bazą danych: " . $e->getMessage();
        ChceckError($erro);
      }
    
    return $conn;
}

// Sprawdzenie czy wystapil blad i przekierowanie na strone z bledem
function ChceckError($error){
    if (is_string($error)) {
        if (strpos($error, "error") === 0) {
            session_unset();
            session_destroy();
            header("Location: ./pages/errorPage.php?error=$error"); exit;
          }
      }
}

// Stworzenie nowego uzytkownika
function SignInUser($conn, $username, $email, $password, $invCode){ 

    $currentData = date("Y-m-d h:i:sa");

    $sql = "INSERT INTO `users`(`ID`, `Nazwa`, `Email`, `Haslo`, `DataStworzenia`) VALUES (NULL,'$username','$email','$password','[value-5]')";
    
    if($result = mysqli_query($conn, $sql)){InsertNewUserData($conn, $username);}

}

// Dodanie rekordów do innych tabel dla nowego uzytkownika
function InsertNewUserData($conn, $username){

}

// Sprawdzenie czy taki uzytkownik juz istnieje
function ChceckUserExist($conn, $username, $email){
    

}


?>