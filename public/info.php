<?php
function test_db() {
	$dsn = "mysql:host=localhost;port=3306;dbname=wax_development";
	$pdo = new PDO($dsn, "wax", "wax");
	$pdo->prepare("SELECT * FROM `user`");
	echo "<pre>";
	print_r($pdo->fetchAll());
	echo "</pre>";
	exit;
}
test_db();
?>