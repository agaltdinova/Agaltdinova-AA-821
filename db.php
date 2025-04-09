<?php
$host = 'localhost';
$dbname = 'ttracker';
$user = 'postgres'; 
$password = 'postgres';  

try {
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC 
    ]);
    
} catch (PDOException $e) {
    echo "Ошибка подключения: " . $e->getMessage();
}
?>
