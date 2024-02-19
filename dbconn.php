<?php
//connect to product db

class dbconn
{
    public $username;
    public $password;
    public $con;
    private $dbName;
    public $hostName;
    public $dbInteractionType;


    function __construct(
            $name,
            $pwd,
            $dbInteractionType,
            $dbName  = "product",
            $hostName = "localhost" ) {
                $this->username = $name;
                $this->password = $pwd;
                $this->dbName = $dbName;
                $this->hostName = $hostName;
                $this->dbInteractionType = $dbInteractionType;

    } //end of constructor

    private function setDBconnect ($connetionType) {
        if (strtolower($connetionType) == "mysqli")
        {
            $this->con = mysqli_connect("localhost",
                                    $this->username,
                $this->password, $name);
        } else if (strtolower($connetionType) == "pdo") {
            $this->con = new PDO("mysql:host=localhost;dbname=$name", $name, $pwd);
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        }

    } //end of setDBconnectdb


$servername = "localhost";
//$username = "epr";
//$password = "9318sAm14m";
$dbname = "product"; // replace with your database name
$con
///mysqli
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error)
{
die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully";
$conn->close();

//PDO
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

//once done
$conn = null;
} //end of class