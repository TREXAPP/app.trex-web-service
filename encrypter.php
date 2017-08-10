<?php

?>
<html>
<body>

<form action="encrypter.php" method="post">
username: <input type="text" name="username"><br>
password: <input type="text" name="password"><br>
<br>
<input type="submit">
</form>
<br>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$valid = true;
    if (isset($_POST["username"])) {
		$username = $_POST["username"];
	} else {
		$valid = false;
	}
	
	if (isset($_POST["password"])) {
		$password = $_POST["password"];
	} else {
		$valid = false;
	}
	
	if (!$valid) {
			echo "Username or Password is empty";
	} else {
		
		$salt = md5($username);
		$output = md5($password . $salt);
		echo "<br/>";
		echo "username = " . $username;
		echo "<br/>";
		echo "md5(username) = " . md5($username);
		echo "<br/>";
		echo "pass + md5(username) = " . $password . md5($username);
		echo "<br/>";
		echo "md5(password + md5(username)) = " . md5($password . md5($username));
		echo "<br/>";
		
		echo "The encrypted string is: <br/>";
		echo "<b>" . md5($password . md5($username)) . "</b>";		
	}
	
	
} else {

}
?>