<?php
session_start();

// Connecting to the database
function ConnectDB(){
    include('keys.php');
    try {
        $conn = mysqli_connect($hostname, $username, $password, $database);
      } catch (Exception $e) {
        $erro = "error: Łączenie z bazą danych: " . $e->getMessage();
        ChceckError($erro);
      }
    
    return $conn;
}

// Checking if an error occurred and redirecting to the error page
function ChceckError($error){
    if (is_string($error)) {
        if (strpos($error, "error") === 0) {
            session_unset();
            session_destroy();
            header("Location: ./pages/errorPage.php?error=$error"); exit;
          }
      }
}

// Create new user
function SignInUser($conn, $username, $email, $password, $invCode){ 

    $currentData = date('Y-m-d H:i:s');

    try{
      if (ChceckUserExist($conn, $username, $email) == 0){     // 1- istnieje 0- nie iestnieje
        $sql = "INSERT INTO `users`(`ID`, `Nazwa`, `Email`, `Haslo`, `DataStworzenia`) VALUES (NULL,'$username','$email','$password','$currentData')";
        if($result = mysqli_query($conn, $sql)){
          if(InsertNewUserData($conn, $username, $invCode))
            return true; 
        }
        else{
          $_SESSION["errorCreate"] = "Uzytkownik nie został poprawnie stworzony";
          header("Location: ./index.php"); exit;
          return false;
        }
      }
      else{
        $_SESSION["errorCreate"] = "Taki uzytkownik juz istnieje";
        header("Location: ./index.php"); exit;
        return false;
      }
    }catch(Exception $e){
        $erro = "error: Tworzenie nowego uzytkownika: " . $e->getMessage();
        ChceckError($erro);
    }
    return false;

}

// Adding records to other tables for a new user
function InsertNewUserData($conn, $username, $invCode){

  // Default value
  $userStatus = 1; // Active account
  $userGroup = 3; // Group "User"
  $startupMoney = 10; 
  $startupTicket = 0; 
  $startupPoints = 0;
  $startupWorld = 0;
  $startupChapter = 0;
  
  $typeOfEarnings = 1;
  $currentData = date('Y-m-d H:i:s');
  $startupProfit = 120;
  
  // Get user ID by username
  try{
    $sqlGetId = "SELECT `ID` FROM `users` WHERE `Nazwa`='$username'";
    $result = $conn -> query($sqlGetId); 
    while($row = $result->fetch_array(MYSQLI_NUM)){
        $userId = $row[0];
    }
    $userCode = generateUniqueCode($conn, $userId);
    
    if(strlen($invCode) == 0) $invCode="000000";

    // Insert data in table "referral codes"
    $sqlInsertInvCode = "INSERT INTO `kodypolecenia`(`ID`, `IDUzytkownika`, `KodPolecajacy`, `KodPolecajacego`) VALUES (NULL,'$userId','$userCode','$invCode')";
    if($resultReferralCode = $conn -> query($sqlInsertInvCode)) 
    {
      // Insert data in table "Status account"
      $sqlAccoountStatus = "INSERT INTO `statuskonta`(`ID`, `IDUzytkownika`, `StatusKonta`, `Grupa`) VALUES (NULL,'$userId','$userStatus','$userGroup')";
      if($resultStatusAccount = $conn -> query($sqlAccoountStatus)) 
      {
        // Insert data in table "Wallet"
        $sqlInsertWallet = "INSERT INTO `portfel`(`ID`, `IDUzytkownika`, `Monety`, `Bilety`, `Punkty`, `Swiat`, `Rozdzial`) VALUES ('','$userId','$startupMoney','$startupTicket','$startupPoints','$startupWorld','$startupChapter')";
        if($resultStatusWallet = $conn -> query($sqlInsertWallet)) 
        {
          // Insert data in table "Activity log"
          $sqlInsertActivityLog ="INSERT INTO `logdzialalnosci`(`ID`, `IDUzytkownika`, `IDTypuZarobku`, `DataZarobku`, `Zarobek`) VALUES (NULL,'$userId','$typeOfEarnings','$currentData','$startupProfit')";
          if($resultStatusActivity = $conn -> query($sqlInsertActivityLog)) 
          {
            // Insert data in table "Skills log 1"
            $sqlInsertSkillsData1 = "INSERT INTO `logumiejetnosci`(`ID`, `IDUzytkownika`, `IDUmiejetnosci`, `Data`, `Koszt`, `Poziom`) VALUES (NULL,'$userId','1','$currentData','3','0')";
            if($resultStatusSkills1 = $conn -> query($sqlInsertSkillsData1)) 
            {
              // Insert data in table "Skills log 2"
              $sqlInsertSkillsData2 = "INSERT INTO `logumiejetnosci`(`ID`, `IDUzytkownika`, `IDUmiejetnosci`, `Data`, `Koszt`, `Poziom`) VALUES (NULL,'$userId','2','$currentData','3','0')";
              if($resultStatusSkills2 = $conn -> query($sqlInsertSkillsData2)) 
              {
                // Insert data in table "Skills log 3"
                $sqlInsertSkillsData3 = "INSERT INTO `logumiejetnosci`(`ID`, `IDUzytkownika`, `IDUmiejetnosci`, `Data`, `Koszt`, `Poziom`) VALUES (NULL,'$userId','3','$currentData','7','0')";
                if($resultStatusSkills3 = $conn -> query($sqlInsertSkillsData3)) 
                {
                  return true;
                }
                else return false;
              }
              else return false;
            }
            else return false;
          }
          else return false;
        }
        else return false;
      }
      else return false;
    }
    else return false;
  }catch(Exception $e){
    $erro = "error: Dodanie rekordów do innych tabel dla nowego uzytkownika: " . $e->getMessage();
    ChceckError($erro);
}
  

}

// Check if the user already exists
function ChceckUserExist($conn, $username, $email){
  $sql = "SELECT COUNT(*) FROM `users` WHERE `Nazwa`='$username' OR `Email`='$email'";
  $result = $conn -> query($sql); 
  while($row = $result->fetch_array(MYSQLI_NUM)){
      return $row[0];
  }

}

// Generating unique codes
function generateUniqueCode($conn, $userId) {

  $length = 6;
  $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $code = '';
  
  for ($i = 0; $i < $length; $i++) {
    $code .= $characters[rand(0, strlen($characters) - 1)];
  }
  
  // Checking if the code already exists in the database
  $sql = "SELECT COUNT(*) FROM `kodypolecenia` WHERE `KodPolecajacy` = '$code'";
  $result = $conn -> query($sql); 
  while($row = $result->fetch_array(MYSQLI_NUM)){
    $stmt = $row[0];
  }
  if ($stmt > 0) {
    // The code already exists, generate a new code
    generateUniqueCode($conn);
  }
  
  return $code;
}


?>