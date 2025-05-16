<?php
$host = "localhost"; 
$username = "root";  
$password = "";      
$dbname = "Lost_and_Found"; 

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully to MySQL!<br>";

    // Test a simple query (e.g., fetch database tables)
    $query = "SHOW TABLES";
    $result = $conn->query($query);

    if ($result) {
        echo "Database tables found: " . $result->num_rows;
    } else {
        echo "Query failed: " . $conn->error;
    }
}

$conn->close(); // Close connection
?>