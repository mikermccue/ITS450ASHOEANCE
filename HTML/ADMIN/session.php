<?php

//require configuration
require('./includes/config.inc.php');
require('./mysql.inc.php');

//start session
session_start();

//echo "hello";

$user_check = $_SESSION['login_user'];

$ses_sql = mysqli_query($dbc,"select username from admin where username = '$user_check' ");
   
   $row = mysqli_fetch_array($ses_sql,MYSQLI_ASSOC);
   
   $login_session = $row['username'];
   
   if(!isset($_SESSION['login_user'])){
      header("location: ./admin/index.php");
   }
?>
