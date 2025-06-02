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
if ($mysqli->connect_error) {
    die("mysqliection failed: " . $mysqli->connect_error);
}

// Fetch all products with category & supplier
$products = $mysqli->query("
    SELECT p.id, p.name, p.stock, p.price, c.name AS category
    FROM products p
    LEFT JOIN category c ON p.category_id = c.id
");

// Total Sales
$totalSalesResult = $mysqli->query("SELECT SUM(total) AS total_sales FROM sales");
$totalSales = $totalSalesResult->fetch_assoc()['total_sales'] ?? 0;

// Total Stocks
$totalStocksResult = $mysqli->query("SELECT SUM(stock) AS total_stock FROM products");
$totalStocks = $totalStocksResult->fetch_assoc()['total_stock'] ?? 0;


// Latest 5 purchases
$purchases = $mysqli->query("
    SELECT pu.purchase_date, pu.invoice_number, pu.total, s.name AS supplier
    FROM purchases pu
    JOIN suppliers s ON pu.supplier_id = s.id
    ORDER BY pu.purchase_date DESC
    LIMIT 5
");

// Latest 5 sales
$sales = $mysqli->query("
    SELECT sa.sale_date, sa.invoice_number, sa.total, u.username
    FROM sales sa
    JOIN users u ON sa.user_id = u.id
    ORDER BY sa.sale_date DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Inventory System - Mia's Bags</title>
  <link rel="stylesheet" href="./assets/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto&display=swap" rel="stylesheet">
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
        <?php elseif ($role === 'cashier'): ?>
          <a href="pos_dashboard.php" class="<?= $current === 'pos_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
          <a href="sales.php" class="<?= $current === 'sales.php' ? 'active' : '' ?>">Sales</a>
        <?php endif; ?>
      </nav>
      <div class="admin-actions">
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
     </aside>
    <main class="main-content">
    <section class="content">
    <!-- Product Stock Levels -->
    <h2>Inventory Dashboard</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $products->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td>₱<?= number_format($row['price'], 2) ?></td>
            <td style="font-weight: bold; color: 
                <?= ($row['stock'] == 0) ? 'gray' : (($row['stock'] <= 10) ? 'red' : 'green'); ?>;">
                <?= $row['stock'] ?>
                <?= ($row['stock'] == 0) ? ' (Out of Stock)' : (($row['stock'] <= 10) ? ' (Low Stock)' : '') ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <div class="totals-container">
        <div class="total-sales">
            <h2 class="sales-heading">Total Sales</h2><br>
            ₱<?= number_format($totalSales, 2) ?>
        </div>

        <div class="total-stocks">
            <h2 class="sales-heading">Total Stocks</h2><br>
            <?= number_format($totalStocks) ?> items
        </div>
    </div>


    <!-- Recent Sales -->
    <h2>Recent Sales</h2>
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Cashier</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $sales->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['invoice_number']) ?></td>
                <td><?= $row['sale_date'] ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td>₱<?= number_format($row['total'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </section>
    </main>
    </div>
</div>
</body>
</html>
