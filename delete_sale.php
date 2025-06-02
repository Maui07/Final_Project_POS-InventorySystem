<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: sales.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_errno) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sale_id = intval($_GET['id']);

// Begin transaction
$mysqli->begin_transaction();

try {
    // Delete from sales_details first
    $stmt1 = $mysqli->prepare("DELETE FROM sales_details WHERE sale_id = ?");
    $stmt1->bind_param("i", $sale_id);
    $stmt1->execute();
    $stmt1->close();

    // Then delete from sales
    $stmt2 = $mysqli->prepare("DELETE FROM sales WHERE id = ?");
    $stmt2->bind_param("i", $sale_id);
    $stmt2->execute();
    $stmt2->close();

    $mysqli->commit();
    header("Location: sales.php?deleted=1");
    exit;
} catch (Exception $e) {
    $mysqli->rollback();
    echo "Error deleting sale: " . $e->getMessage();
}
?>
