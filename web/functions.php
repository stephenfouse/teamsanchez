<?php
session_start();

function getDBConnection() {
	if (empty($_SESSION['dbConnect'])){
		$host = "localhost";
		$user = "teamsanchez";
		$pass = "password";
		$db   = "sanchez";

		$r = mysql_connect($host, $user, $pass);

		if (!$r) {
			echo "Could not connect to server\n";
			trigger_error(mysql_error(), E_USER_ERROR);
			return NULL;
		}

		$_SESSION['dbConnect'] = &$r;
		mysql_select_db($db);
	}

	return $_SESSION['dbConnect'];
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

function beginTransaction(){
    mysql_query("BEGIN");
}

function commitTransaction(){
    mysql_query("COMMIT");
}

function rollbackTransaction(){
    mysql_query("ROLLBACK");
}

function checkError(&$rs, &$commitErr) {
	if (!$rs) {
		array_push($commitErr, mysql_error());
		return 1;
	}

	return 0;
}
?>
