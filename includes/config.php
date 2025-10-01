<?php
$host = "dpg-d3eq5rumcj7s73dvr4sg-a";
$port = "5432";
$dbname = "cx7670";
$user = "cx7670_xid7_user";
$password = "uv26wxOj3EqtYfGCp8NJyAOEudNkxdUI";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
