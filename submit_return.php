<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchaseId = $_POST['purchase_id'];
    $productId = $_POST['product_id'];
    $reason = trim($_POST['reason']);
    $quantity = intval($_POST['quantity']);
    $refund = floatval(preg_replace('/[^\d.]/', '', $_POST['refund_amount']));


    $mysqli = new mysqli("localhost", "root", "", "project02");
    if ($mysqli->connect_errno) {
        $_SESSION['toast'] = ['message' => 'Database connection failed.', 'success' => false];
        header("Location: returns.php");
        exit;
    }
    

    // Get already returned quantity
    $check = $mysqli->prepare("SELECT pd.quantity, 
        (SELECT IFNULL(SUM(rd.quantity), 0) 
        FROM returns r 
        JOIN return_details rd ON r.id = rd.return_id 
        WHERE r.purchase_id = ? AND rd.product_id = ?) AS already_returned
        FROM purchases_details pd
        WHERE pd.purchase_id = ? AND pd.product_id = ?");
    $check->bind_param("iiii", $purchaseId, $productId, $purchaseId, $productId);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $remaining = $result['quantity'] - $result['already_returned'];

    if ($quantity <= 0 || $quantity > $remaining) {
        $_SESSION['toast'] = ['message' => 'Invalid return quantity.', 'success' => false];
        header("Location: returns.php");
        exit;
    }

    $mysqli->begin_transaction();

    try {
        // Create return record
        $stmt = $mysqli->prepare("INSERT INTO returns (purchase_id, return_date, reason) VALUES (?, NOW(), ?)");
        $stmt->bind_param("is", $purchaseId, $reason);
        $stmt->execute();
        $returnId = $stmt->insert_id;

        // Add return detail
        $stmt = $mysqli->prepare("INSERT INTO return_details (return_id, product_id, quantity, refund_amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $returnId, $productId, $quantity, $refund);
        $stmt->execute();

        // Update product stock (optional)
        $stmt = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $productId);
        $stmt->execute();

        $mysqli->commit();
        $_SESSION['toast'] = ['message' => 'Return submitted successfully.', 'success' => true];
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['toast'] = ['message' => 'Return failed: ' . $e->getMessage(), 'success' => false];
    }

    $stmt->close();
    $mysqli->close();
}

header("Location: returns.php");
exit;
