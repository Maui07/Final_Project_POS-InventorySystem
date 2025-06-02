<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['cashier', 'admin'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];

$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get cashier's user_id
$user_stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_stmt->bind_result($user_id);
$user_stmt->fetch();
$user_stmt->close();

$products = $_POST['products'];
$quantities = $_POST['quantities'];
$prices = $_POST['prices'];
$total = $_POST['total'];

// Validate stock and input
for ($i = 0; $i < count($products); $i++) {
    $pid = intval($products[$i]);
    $qty = intval($quantities[$i]);
    $stock_result = $mysqli->query("SELECT stock FROM products WHERE id = $pid");
    $stock_row = $stock_result->fetch_assoc();
    if ($qty > $stock_row['stock']) {
        die("Not enough stock for product ID $pid.");
    }
}

// Generate a unique invoice number
$invoice_number = generateInvoiceNumber($mysqli);

// Insert sale
$sale_stmt = $mysqli->prepare("INSERT INTO sales (user_id, sale_date, total, invoice_number) VALUES (?, NOW(), ?, ?)");
$sale_stmt->bind_param("ids", $user_id, $total, $invoice_number);
$sale_stmt->execute();
$sale_id = $sale_stmt->insert_id;
$sale_stmt->close();

// Insert sale details and update stock
for ($i = 0; $i < count($products); $i++) {
    $pid = intval($products[$i]);
    $qty = intval($quantities[$i]);
    $price = floatval($prices[$i]);
    
    // Insert sale detail
    $detail_stmt = $mysqli->prepare("INSERT INTO sales_details (sale_id, product_id, quantity, selling_price) VALUES (?, ?, ?, ?)");
    $detail_stmt->bind_param("iiid", $sale_id, $pid, $qty, $price);
    $detail_stmt->execute();
    $detail_stmt->close();
    
    // Update product stock
    $mysqli->query("UPDATE products SET stock = stock - $qty WHERE id = $pid");
}

$mysqli->close();

// Redirect to sales page with success message
header("Location: pos_dashboard.php?success=1&sale_id=" . $sale_id);
exit;

// Function to generate a unique invoice number
function generateInvoiceNumber($mysqli) {
    $result = $mysqli->query("SELECT MAX(CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED)) AS max_invoice FROM sales");
    $row = $result->fetch_assoc();
    $new_number = $row['max_invoice'] ? $row['max_invoice'] + 1 : 1; // Start from 1 if no previous invoices
    return 'INV-' . str_pad($new_number, 4, '0', STR_PAD_LEFT); // Format: INV-0001
}
