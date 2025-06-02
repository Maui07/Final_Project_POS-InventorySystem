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
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Fetch all purchases with product info
$query = "
SELECT
    pur.id AS purchase_id,
    pur.invoice_number,
    pur.purchase_date,
    sup.name AS supplier_name,
    pd.product_id,
    p.name AS product_name,
    pd.quantity,
    pd.cost_price,
    (
        SELECT IFNULL(SUM(rd.quantity), 0)
        FROM returns r
        JOIN return_details rd ON r.id = rd.return_id
        WHERE r.purchase_id = pur.id AND rd.product_id = pd.product_id
    ) AS already_returned,
    (
        SELECT r.id
        FROM returns r
        JOIN return_details rd ON r.id = rd.return_id
        WHERE r.purchase_id = pur.id AND rd.product_id = pd.product_id
        ORDER BY r.id DESC
        LIMIT 1
    ) AS return_id
FROM purchases pur
JOIN suppliers sup ON pur.supplier_id = sup.id
JOIN purchases_details pd ON pd.purchase_id = pur.id
JOIN products p ON pd.product_id = p.id
ORDER BY pur.purchase_date DESC
";

$result = $mysqli->query($query);
if (!$result) {
    die("Query failed: (" . $mysqli->errno . ") " . $mysqli->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Purchase Returns - Mia's Bags</title>
    <link rel="stylesheet" href="./assets/style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto&display=swap" rel="stylesheet" />
</head>
<body>
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
            <h2>Purchase Returns</h2>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th>Product</th>
                        <th>Purchased Quantity</th>
                        <th>Returned Quantity</th>
                        <th>Cost</th>
                        <th>Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php $remaining = $row['quantity'] - $row['already_returned']; ?>
                        <tr>
                            <td><?= htmlspecialchars($row['invoice_number']) ?></td>
                            <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td><?= $row['already_returned'] ?></td>
                            <td>₱<?= number_format($row['cost_price'], 2) ?></td>
                            <td><?php if ($row['return_id']): ?>
                            <button 
                                class="btn-view" 
                                data-return-id="<?= $row['return_id'] ?>">
                                View
                            </button>
                            <?php else: ?>
                                <button class="btn-view" disabled>No Returns</button>
                            <?php endif; ?>
                            </td>
                            <td>
                            <button 
                                class="btn-return" 
                                data-purchase-id="<?= $row['purchase_id'] ?>"
                                data-product-id="<?= $row['product_id'] ?>"
                                data-remaining="<?= $remaining ?>"
                                data-cost="<?= $row['cost_price'] ?>"
                                <?= $remaining <= 0 ? 'disabled' : '' ?>>
                                Return
                            </button>
                        </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Return Modal -->
            <div id="returnModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3 id="modal-title">Return</h3>
                    <form action="submit_return.php" method="POST">
                        <input type="hidden" name="purchase_id" id="modalPurchaseId">
                        <input type="hidden" name="product_id" id="modalProductId">
                        <input type="hidden" name="cost_price" id="modalCostPrice"> <br>

                        <label for="reason">Reason for Return:</label>
                        <textarea name="reason" required></textarea><br><br>

                        <label for="quantity">Quantity to Return:</label>
                        <input type="number" name="quantity" id="modalQuantity" required min="1"><br><br>

                        <label for="refund">Refund Amount:</label>
                        <input type="text" id="modalRefund" name="refund_amount" readonly required> <br><br>

                        <button type="submit" class="btn-add">Submit Return</button>
                    </form>
                </div>
            </div>

            <!-- View Details Modal -->
            <div id="viewDetailsModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div id="detailsContainer">Loading...</div>
                </div>
            </div>
        </section>
    </div>
</div>
<?php if (isset($_SESSION['toast'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const toast = document.getElementById('toast');
        toast.textContent = <?= json_encode($_SESSION['toast']['message']) ?>;
        toast.style.background = <?= $_SESSION['toast']['success'] ? "'#28a745'" : "'#dc3545'" ?>;
        toast.style.display = 'block';
        setTimeout(() => toast.style.display = 'none', 3000);
    });
</script>
<?php unset($_SESSION['toast']); endif; ?>
<script src="./assets/js/returns.js"></script>
</body>
</html>
