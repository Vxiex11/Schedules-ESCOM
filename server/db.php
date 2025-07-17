<?php
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "escom_schedule"; 

$conn = new mysqli($host, $user, $pass, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
