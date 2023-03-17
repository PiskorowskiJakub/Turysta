<?php
include('database.php');

function SignIn(){
    if(isset($_POST['signin'])){
        
        header('Location: ./main.php'); exit;
    }
}




?>