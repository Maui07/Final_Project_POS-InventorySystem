<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_errno) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_number = $_POST['invoice_number'];
    $supplier_id = $_POST['supplier_id'];
    $purchase_date = date("Y-m-d");

    $products_id = $_POST['products_id'];
    $quantities = $_POST['quantity'];
    $cost_prices = $_POST['cost_price'];

    if (count($products_id) !== count($quantities) || count($quantities) !== count($cost_prices)) {
        die("Mismatched data input.");
    }

    // Insert into purchases table
    $stmt = $mysqli->prepare("INSERT INTO purchases (invoice_number, supplier_id, purchase_date, status) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("sis", $invoice_number, $supplier_id, $purchase_date);
    $stmt->execute();
    $purchase_id = $stmt->insert_id;
    $stmt->close();

    // Insert into purchases_details table
    $stmt_detail = $mysqli->prepare("INSERT INTO purchases_details (purchase_id, product_id, quantity, cost_price) VALUES (?, ?, ?, ?)");
    for ($i = 0; $i < count($products_id); $i++) {
        $product_id = $products_id[$i];
        $quantity = $quantities[$i];
        $cost_price = $cost_prices[$i];
        $stmt_detail->bind_param("iiid", $purchase_id, $product_id, $quantity, $cost_price);
        $stmt_detail->execute();
    }
    $stmt_detail->close();

    header("Location: purchases.php");
    exit;
} else {
    die("Invalid request method.");
}
?>
