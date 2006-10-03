<?php
$dsn = "mysql:host=localhost;port=3306;dbname=wax_development";
$pdo = new PDO($dsn, "wax", "wax");
function test_db() {
	$sth = $pdo->prepare("SELECT * FROM `user`");
}
test_db();
echo "<pre>";
print_r($sth);
echo "</pre>";
exit;
?>