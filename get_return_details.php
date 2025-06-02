<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['return_id'])) {
    echo "No return ID specified.";
    exit;
}

$return_id = intval($_GET['return_id']);

$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Get the return record with purchase info
$query = "
SELECT 
    r.id,
    r.reason,
    r.return_date,
    pur.invoice_number,
    pur.purchase_date,
    s.name AS supplier_name
FROM returns r
JOIN purchases pur ON r.purchase_id = pur.id
JOIN suppliers s ON pur.supplier_id = s.id
WHERE r.id = ?
";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $return_id);
$stmt->execute();
$purchase = $stmt->get_result()->fetch_assoc();

if (!$purchase) {
    echo "Return record not found.";
    exit;
}

// Get all return details for this return (likely one or more products)
$query = "
SELECT 
    p.name AS product_name,
    rd.quantity,
    rd.refund_amount
FROM return_details rd
JOIN products p ON rd.product_id = p.id
WHERE rd.return_id = ?
";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $return_id);
$stmt->execute();
$res = $stmt->get_result();

$details = [];
while ($row = $res->fetch_assoc()) {
    $details[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head><title>Return Details</title></head>
<body>
    <h3 id="modal-title">Return Details</h3>
    <p><strong>Invoice Number:</strong> <?= htmlspecialchars($purchase['invoice_number']) ?></p>
    <p><strong>Purchase Date:</strong> <?= htmlspecialchars($purchase['purchase_date']) ?></p>
    <p><strong>Supplier:</strong> <?= htmlspecialchars($purchase['supplier_name']) ?></p> <br>
    <p><strong>Return Reason:</strong> <?= htmlspecialchars($purchase['reason']) ?></p>
    <p><strong>Return Date:</strong> <?= htmlspecialchars($purchase['return_date']) ?></p>

    <?php if (count($details) > 0): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Product Name</th>
                <th>Returned Quantity</th>
                <th>Refund Amount</th>
            </tr>
            <?php foreach ($details as $detail): ?>
                <tr>
                    <td><?= htmlspecialchars($detail['product_name']) ?></td>
                    <td><?= (int)$detail['quantity'] ?></td>
                    <td>₱<?= number_format($detail['refund_amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No product return details found.</p>
    <?php endif; ?>
</body>
</html>
