<?php 
session_start();
if (!isset($_SESSION['role']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_errno) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (!isset($_POST['selected_ids']) || !is_array($_POST['selected_ids'])) {
    header("Location: purchases.php?error=invalid_input");
    exit;
}

$mysqli->begin_transaction();

try {
    foreach ($_POST['selected_ids'] as $purchase_id) {
    // Fetch purchase status and supplier
    $check = $mysqli->prepare("SELECT status, supplier_id FROM purchases WHERE id = ?");
    $check->bind_param("i", $purchase_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$result) {
        throw new Exception("Purchase ID $purchase_id not found.");
    }

    if ($result['status'] == 1) {
        // Already posted, skip
        continue;
    }

    $supplier_id = $result['supplier_id'];

    // Get all purchase details (products and quantities)
    $details = $mysqli->prepare("SELECT product_id, quantity FROM purchases_details WHERE purchase_id = ?");
    $details->bind_param("i", $purchase_id);
    $details->execute();
    $resDetails = $details->get_result();

    while ($row = $resDetails->fetch_assoc()) {
        $product_id = $row['product_id'];
        $qty = $row['quantity'];

        // Update product stock and supplier_id
        $update = $mysqli->prepare("UPDATE products SET stock = stock + ?, supplier_id = ? WHERE id = ?");
        $update->bind_param("iii", $qty, $supplier_id, $product_id);
        if (!$update->execute()) {
            throw new Exception("Failed to update stock and supplier for product ID $product_id");
        }
        $update->close();
    }
    $details->close();

    // Mark purchase as posted
    $stmt = $mysqli->prepare("UPDATE purchases SET status = 1 WHERE id = ?");
    $stmt->bind_param("i", $purchase_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update purchase status for purchase ID $purchase_id");
    }
    $stmt->close();
    }

    $mysqli->commit();
} catch (Exception $e) {
    $mysqli->rollback();
    die("Transaction failed: " . $e->getMessage());
}

$mysqli->close();

header("Location: purchases.php?posted=success");
exit;
?>
