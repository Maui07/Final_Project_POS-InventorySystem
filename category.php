<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$current = basename($_SERVER['PHP_SELF']);

$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (isset($_POST['save_category'])) {
    $name = trim($_POST['category_name']);
    $id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    if ($name !== '') {
        if ($id > 0) {
            $stmt = $mysqli->prepare("UPDATE category SET name=? WHERE id=?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $mysqli->prepare("INSERT INTO category (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (isset($_GET['delete_category'])) {
    $id = intval($_GET['delete_category']);
    $stmt = $mysqli->prepare("DELETE FROM category WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

$openCategoryModal = false;
if (isset($_SESSION['open_category_modal'])) {
    $openCategoryModal = true;
    unset($_SESSION['open_category_modal']); // clear flag after use
}

header("Location: products.php");
exit;
?>