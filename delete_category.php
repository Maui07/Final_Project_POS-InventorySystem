<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$category_id = intval($_GET['id']);

$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$stmt = $mysqli->prepare("DELETE FROM category WHERE id=?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$stmt->close();

$mysqli->close();

header("Location: products.php");
exit;
?>