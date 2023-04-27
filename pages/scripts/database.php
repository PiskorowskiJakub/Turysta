<?php
  include('querys.php');


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
        global $sqlCreateNewUser;
        $resultCreateNewUser = ExecuteQuery($conn, 'e', $sqlCreateNewUser, 's', $username, 's', $email, 's', $password, 's', $currentData);

        if($resultCreateNewUser){
          if(InsertNewUserData($conn, $email, $invCode))
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
function InsertNewUserData($conn, $email, $invCode){

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
  $startupProfit = 180;
  
  // Get user ID by username
  try{
    global $sqlGetUserId;
    $resultGetUserId = ExecuteQuery($conn, 's', $sqlGetUserId, 's', $email);
 
    while($row = $resultGetUserId->fetch_array(MYSQLI_NUM)){
        $userId = $row[0];
    }
    $userCode = GenerateUniqueCode($conn, $userId);
    
    if(strlen($invCode) == 0) $invCode="000000";

    // Insert data in table "referral codes"
    global $sqlInsertInvCode;
    if($resultInsertInvCode = ExecuteQuery($conn, 'e', $sqlInsertInvCode, 'i', $userId, 's', $userCode, 's', $invCode))
    {
      // Insert data in table "Status account"
      global $sqlAccoountStatus;
      if($resultAccoountStatus = ExecuteQuery($conn, 'e', $sqlAccoountStatus, 'i', $userId, 'i', $userStatus, 'i', $userGroup))
      {
        // Insert data in table "Wallet"
        global $sqlInsertWallet;
        if($resultInsertWallet = ExecuteQuery($conn, 'e', $sqlInsertWallet, 'i', $userId, 'd', $startupMoney, 'i', $startupTicket, 'd', $startupPoints, 'i', $startupWorld, 'i', $startupChapter))
        {
          // Insert data in table "Activity log"
          global $sqlInsertWorkMarketLog;
          if($resultInsertWorkMarketLog = ExecuteQuery($conn, 'e', $sqlInsertWorkMarketLog, 'i', $userId, 'i', $typeOfEarnings, 's', $currentData, 'd', $startupProfit, 'i', 1))
          {
            // Insert data in table "Skills log 1"
            global $sqlInsertSkillsData1;
            if($resultInsertSkillsData1 = ExecuteQuery($conn, 'e', $sqlInsertSkillsData1, 'i', $userId, 's', $currentData))
            {
              // Insert data in table "Skills log 2"
              global $sqlInsertSkillsData2;
              if($resultInsertSkillsData2 = ExecuteQuery($conn, 'e', $sqlInsertSkillsData2, 'i', $userId, 's', $currentData))
              {
                // Insert data in table "Skills log 3"
                global $sqlInsertSkillsData3;
                if($resultInsertSkillsData3 = ExecuteQuery($conn, 'e', $sqlInsertSkillsData3, 'i', $userId, 's', $currentData))
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
  global $sqlCheckUserExist;
  $resultCheckUserExist = ExecuteQuery($conn, 's', $sqlCheckUserExist, 's', $username, 's', $email);
  while($row = $resultCheckUserExist->fetch_array(MYSQLI_NUM)){
      return $row[0];
  }

}

// Generating unique codes
function GenerateUniqueCode($conn, $userId) {

  $length = 6;
  $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $code = '';
  
  for ($i = 0; $i < $length; $i++) {
    $code .= $characters[rand(0, strlen($characters) - 1)];
  }
  
  global $sqlCodeExist;
  $resultCodeexist = ExecuteQuery($conn, 's', $sqlCodeExist, 's', $code);
  while($row = $resultCodeexist->fetch_array(MYSQLI_NUM)){
    $stmt = $row[0];
  }
  if ($stmt > 0) {
    // The code already exists, generate a new code
    GenerateUniqueCode($conn);
  }
  
  return $code;
}

//----------------------------------------------------------------------
// Login system
//----------------------------------------------------------------------

function CheckIfRegistered($conn, $emailForm, $passwordForm){
  global $sqlFindUser;
  $resultFindUser = ExecuteQuery($conn, 's', $sqlFindUser, 's', $emailForm);
  while($row = $resultFindUser->fetch_array(MYSQLI_NUM)){
    $email = $row[0];
    $password = $row[1];
  }


  if($email != $emailForm){
    $_SESSION["errorLogin"] = "Niepoprawny adres email ";
    header("Location: ./index.php"); exit;
  }
  else if(!password_verify($passwordForm, $password)){
    $_SESSION["errorLogin"] = "Niepoprawne hasło";
    header("Location: ./index.php"); exit;
  }
  else if($email == $emailForm && password_verify($passwordForm, $password))
    return true;
  else{
    $_SESSION["errorLogin"] = "Taki uzytkownik nie istnieje ";
    header("Location: ./index.php"); exit;
  }
  return false;
}

function GetUserId($conn, $email){
  try{
    global $sqlGetUserId;
    $resultGetUserId = ExecuteQuery($conn, 's', $sqlGetUserId, 's', $email);
    while($row = $resultGetUserId->fetch_array(MYSQLI_NUM))
      return $row[0];

  }catch(Exception $e){
    $erro = "error: Pobranie id uzytkownika z bazy danych: " . $e->getMessage();
    ChceckError($erro);
  }
}

function InsertUserLogin($conn, $userId, ){
  $currentData = date('Y-m-d H:i:s');
  global $sqlInsertUserLogin;
  if($resultInsertUserLogin = ExecuteQuery($conn, 'e', $sqlInsertUserLogin, 'i', $userId, 's', $currentData, 's', $currentData))
    return true;
  else
    return false;
}

function GetUserData($conn, $userId){
  try{
    global $sqlGetUserProfileData;
    $resultGetUserProfileData = ExecuteQuery($conn, 's', $sqlGetUserProfileData, 'i', $userId);
    while($row = $resultGetUserProfileData->fetch_array(MYSQLI_NUM)){
      $_SESSION["userName"] = $row[0];
      $_SESSION["userEmail"] = $row[1];
      $_SESSION["userDataCreated"] = $row[2];
      $_SESSION["userStatusAccount"] = $row[3];
      $_SESSION["userStatusGroup"] = $row[4];
      $_SESSION["userMoney"] = $row[5];
      $_SESSION["userTicket"] = $row[6];
      $_SESSION["userPoints"] = $row[7];
      $_SESSION["userWorld"] = $row[8];
      $_SESSION["userChapter"] = $row[9];
    }
  }catch(Exception $e){
    $erro = "error: Pobranie danych uzytkownika z bazy danych: " . $e->getMessage();
    ChceckError($erro);
  }
}

// Method to execute sql query
function ExecuteQuery($conn, $typeSql, $sql, $type1, $parm1, $type2=null, $parm2=null, $type3=null, $parm3=null, $type4=null, $parm4=null, $type5=null, $parm5=null, $type6=null, $parm6=null, $type7=null, $parm7=null, $type8=null, $parm8=null){

  // types: i-integer, d-float, s-string, 
  $stmt = $conn->prepare($sql);
  $type = $type1.$type2.$type3.$type4.$type5.$type6.$type7.$type8;
  if($type2 == null) $stmt->bind_param($type, $parm1);
  else if($type3 == null) $stmt->bind_param($type, $parm1, $parm2);
  else if ($type4 == null) $stmt->bind_param($type, $parm1, $parm2, $parm3);
  else if ($type5 == null) $stmt->bind_param($type, $parm1, $parm2, $parm3, $parm4);
  else if ($type6 == null) $stmt->bind_param($type, $parm1, $parm2, $parm3, $parm4, $parm5);
  else if ($type7 == null) $stmt->bind_param($type, $parm1, $parm2, $parm3, $parm4, $parm5, $parm6);
  else if ($type8 == null) $stmt->bind_param($type, $parm1, $parm2, $parm3, $parm4, $parm5, $parm6, $parm7);
  else if ($type8 != null) $stmt->bind_param($type, $parm1, $parm2, $parm3, $parm4, $parm5, $parm6, $parm7, $parm8);
  $stmt->execute();
  if($typeSql == 's'){ // query SELECT
    $result = $stmt->get_result();
    return $result;
  }else if($typeSql == 'e'){ // query EXECUTE (insert, drop, delete, update)
    return $stmt;
  }
}

//----------------------------------------------------------------------
// Logout 
//----------------------------------------------------------------------

function LogoutUser(){
  global $sqlLogoutUser;
  $userId = $_SESSION["userId"];

  $conn = ConnectDB();
  $currentData = date('Y-m-d H:i:s');
  if($resultLogoutUser = ExecuteQuery($conn, 'e', $sqlLogoutUser, 's', $currentData,'i', $userId,))

  session_unset(); // usuwamy wszystkie zmienne sesyjne
  session_destroy(); // niszczymy sesję

  // Sprawdzanie, czy sesja została usunięta
  if (empty($_SESSION)) {
    header("Location: ./index.php"); exit;
  } else {
    $erro = "error: Wylogowywanie nie powiodło się";
    ChceckError($erro);
  }
  
}

?>