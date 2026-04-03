<?php
$servername = "localhost";
$username = "root";
$password = "Skon1234";
$dbname = "my_site";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

$conn->close();

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read the SQL file
$sql = file_get_contents('my_site.sql');

if ($conn->multi_query($sql) === TRUE) {
    echo "SQL file imported successfully\n";
} else {
    echo "Error importing SQL: " . $conn->error . "\n";
}

$conn->close();
?>