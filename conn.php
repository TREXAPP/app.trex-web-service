<?php
//connection to mysql database
$servername = "localhost";
$username = "sidmk_trexadmin";
$password = "AdmintreX123$";
$database = "sidmk_trexapp";

 
// Create connection
$conn=mysqli_connect($servername,$username,$password,$database);

if (mysqli_connect_errno($con))
{
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
} else { 
//echo "db success<br/><br/>";
}

?>