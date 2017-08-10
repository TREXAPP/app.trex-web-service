<?php
//sending the query as a POST variable
//receiving a table in return
//Igor J. - 15.10.2016 23:56

require 'conn.php';

$query = urldecode($_POST['query']);

if ($query) {
	$result = mysqli_query($conn,$query);
	if ($result) {
		$myArray = array();
		while($row = $result->fetch_array(MYSQL_ASSOC)) {
			$myArray[] = $row;
		}
		echo json_encode($myArray);
	}	
}
 
else {
	echo "You entered an empty query";
}

?>