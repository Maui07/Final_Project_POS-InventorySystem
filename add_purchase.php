<?php
session_start();
include 'db.php'; // Ensure this file contains the correct database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_id = $_POST['supplier_id'];
    $quantity = $_POST['quantity'];
    $cost_price = $_POST['cost_price'];
    $invoice_number = $_POST['invoice_number'];
    $purchase_date = date("Y-m-d");
    $user_id = $_SESSION['user_id'];

    // Check if new purchase was entered
    if (!empty($_POST['new_purchase'])) {
        $new_purchase = trim($_POST['new_purchase']);

        // Insert new purchase
        $stmt = $mysqli->prepare("INSERT INTO purchases (name, stock) VALUES (?, 0)");
        if ($stmt) {
            $stmt->bind_param("s", $new_purchase);
            $stmt->execute();
            $purchase_id = $mysqli->insert_id; // Get the ID of the new purchase
            $stmt->close();
        } else {
            die("Failed to prepare statement: " . $mysqli->error);
        }
    } else {
        // Use existing selected purchase
        $purchase_id = $_POST['purchase_id'];
    }

    // Insert into purchase_purchases
    $stmt = $mysqli->prepare("INSERT INTO purchase_purchases (supplier_id, purchase_id, user_id, quantity, cost_price, invoice_number, purchase_date)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiiidss", $supplier_id, $purchase_id, $user_id, $quantity, $cost_price, $invoice_number, $purchase_date);
        $stmt->execute();
        $stmt->close();
    } else {
        die("Failed to prepare statement: " . $mysqli->error);
    }

    // Update stock
    $stmt = $mysqli->prepare("UPDATE purchases SET stock = stock + ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $quantity, $purchase_id);
        $stmt->execute();
        $stmt->close();
    } else {
        die("Failed to prepare statement: " . $mysqli->error);
    }

    echo "<p>purchase purchase recorded successfully!</p>";
}
?>
