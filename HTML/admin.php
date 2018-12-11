<?php 
//echo "hello";    

//require configuration before any php code
require('./includes/config.inc.php');

//start the session
session_start();

//require database connection
require(MYSQL);

if($_SERVER["REQUEST_METHOD"] == "POST") {
      // username and password sent from form 
	$Password = SHA1($_POST['password']);      
      
      $myusername = mysqli_real_escape_string($dbc,$_POST['username']);
      $mypassword = mysqli_real_escape_string($dbc, $Password); 
      
      $sql = "SELECT id FROM admin WHERE username = '$myusername' and passcode = '$mypassword'";
      $result = mysqli_query($dbc,$sql);
	if (!$result) {
    printf("Error: %s\n", mysqli_error($dbc));
    exit();
}
      $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
      //$active = $row['active'];
      
      $count = mysqli_num_rows($result);
      
      // If result matched $myusername and $mypassword, table row must be 1 row

      if($count == 1) {
         //session_register("myusername");
         $_SESSION['login_user'] = $myusername;
         header("Location: https://www.ashoeance.com/admin/index.php");
	exit();
      }else {
         $error = "Your Login Name or Password is invalid";
	echo $error;
      }
   }
?>

<html>
   
   <head>
      <title>Login Page</title>
      
      <style type = "text/css">
         body {
            font-family:Arial, Helvetica, sans-serif;
            font-size:14px;
         }
         label {
            font-weight:bold;
            width:100px;
            font-size:14px;
         }
         .box {
            border:#666666 solid 1px;
         }
      </style>

	<script> function goBack(){
		window.history.back()
} </script>
      
   </head>
   
   <body bgcolor = "#FFFFFF">
	
      <div align = "center">
         <div style = "width:300px; border: solid 1px #333333; " align = "left">
            <div style = "background-color:#333333; color:#FFFFFF; padding:3px;"><b>Login</b></div>
				
            <div style = "margin:30px">
               
               <form action = "" method = "POST">
                  <label>UserName  :</label><input type = "text" name = "username" class = "box"/><br /><br />
                  <label>Password  :</label><input type = "password" name = "password" class = "box" /><br/><br />
                  <input type = "submit" value = " Submit "/><br />
               </form>					
            </div>
				
         </div>
		
	<button onclick="goBack()">Go Back</button>	
      </div>

   </body>
</html>
