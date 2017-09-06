<?php
//connection to mysql database
$servername = "localhost";
$username = "sidmk_trexadmin";
$password = "AdmintreX123$";
$database = "sidmk_trexapp";

 
// Create connection
$conn=mysqli_connect($servername,$username,$password,$database);
mysqli_set_charset($conn,"utf8");
if (mysqli_connect_errno($conn))
{
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
} else { 
//echo "db success<br/><br/>";
}
mysql_query("SET NAMES UTF8");
?>