<?php
// Test case 1: Valid connection using mysqli
$db = new dbconn("username", "password", "mysqli");
$this->assertNotNull($db);

// Test case 2: Invalid connection using mysqli
$db = new dbconn("username", "password", "mysql");
$this->assertNull($db);

// Test case 3: Valid connection using PDO
$db = new dbconn("username", "password", "pdo");
$this->assertNotNull($db);

// Test case 4: Invalid connection using PDO
$db = new dbconn("username", "password", "mysql");
$this->assertNull($db);// Test connection using mysqli
$db = new dbconn("username", "password", "mysqli");
if (!empty($db->con)) {
    $this->assertNotNull($db->con);
}
mysqli_close($db->con);

// Test connection using PDO
$db = new dbconn("username", "password", "pdo");
$this->assertNotNull($db->con);
$db = null;

// Test connection failure using mysqli
$db = new dbconn("username", "wrongpassword", "mysqli");
$this->assertNull($db->con);

// Test connection failure using PDO
$db = new dbconn("username", "wrongpassword", "pdo");
$this->assertNull($db->con);
