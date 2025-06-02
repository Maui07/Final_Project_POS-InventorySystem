<?php
include 'db.php';

if (isset($_GET['id'])) {
    $purchase_id = intval($_GET['id']);

    // Delete purchase details first (foreign key dependency)
    $stmt = $mysqli->prepare("DELETE FROM purchase_details WHERE purchase_id = ?");
    $stmt->bind_param("i", $purchase_id);
    $stmt->execute();
    $stmt->close();

    // Then delete purchase
    $stmt = $mysqli->prepare("DELETE FROM purchases WHERE id = ?");
    $stmt->bind_param("i", $purchase_id);
    if ($stmt->execute()) {
        $stmt->close();
        $mysqli->close();
        header("Location: purchases.php?msg=Purchase deleted successfully");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
