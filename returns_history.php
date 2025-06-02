<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$current = basename($_SERVER['PHP_SELF']);

$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Fetch all returns with related data
$query = "
SELECT r.id AS return_id, r.purchase_id, r.return_date, r.reason,
       p.invoice_number, s.name AS supplier_name, rd.product_id, pr.name AS product_name,
       rd.quantity, rd.refund_amount
FROM returns r
JOIN purchases p ON r.purchase_id = p.id
JOIN suppliers s ON p.supplier_id = s.id
JOIN return_details rd ON r.id = rd.return_id
JOIN products pr ON rd.product_id = pr.id
ORDER BY r.return_date DESC
";


$result = $mysqli->query($query);
if (!$result) {
    die("Query failed: " . $mysqli->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Returns History - Mia's Bags</title>
    <link rel="stylesheet" href="./assets/style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto&display=swap" rel="stylesheet" />
</head>
<div class="dashboard">
    <aside class="sidebar">
        <h2 class="brand">Mia's Bags</h2>
        <nav class="nav-links">
            <?php if ($role === 'admin'): ?>
                <a href="inventory_dashboard.php" class="<?= $current === 'inventory_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="user_management.php" class="<?= $current === 'user_management.php' ? 'active' : '' ?>">Users</a>
                <a href="products.php" class="<?= $current === 'products.php' ? 'active' : '' ?>">Products</a>
                <a href="suppliers.php" class="<?= $current === 'suppliers.php' ? 'active' : '' ?>">Suppliers</a>
                <a href="purchases.php" class="<?= $current === 'purchases.php' ? 'active' : '' ?>">Purchases</a>
                <a href="sales.php" class="<?= $current === 'sales.php' ? 'active' : '' ?>">Sales</a>
                <a href="returns.php" class="<?= $current === 'returns.php' ? 'active' : '' ?>">Returns</a>
                <a href="returns_history.php" class="<?= $current === 'returns_history.php' ? 'active' : '' ?>">Returns History</a>
            <?php endif; ?>
        </nav>
        <div class="admin-actions">
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </aside>

    <div class="main-content">
        <section class="content">
            <h2>Returns History</h2>

            <table class="styled-table">
                <thead>
            <tr>
                <th>Return ID</th>
                <th>Invoice</th>
                <th>Supplier</th>
                <th>Return Date</th>
                <th>Reason</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Refund</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['return_id']) ?></td>
                <td><?= htmlspecialchars($row['invoice_number']) ?></td>
                <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                <td><?= htmlspecialchars($row['return_date']) ?></td>
                <td><?= htmlspecialchars($row['reason']) ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= htmlspecialchars($row['quantity']) ?></td>
                <td><?= number_format($row['refund_amount'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</div>
</body>
</html>
