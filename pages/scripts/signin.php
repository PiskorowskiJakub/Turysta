<?php
function SignIn(){
    if(isset($_POST['signin'])){
        header('Location: ./main.php'); exit;
    }
}



/*
if(isset($_POST['wybor'])){
    if($_POST['wybor'] == 'm') {header('Location: ./main.php'); exit;}
    //else if($_POST['wybor'] == 'l') include("./pages/login.php");
}
*/
?>