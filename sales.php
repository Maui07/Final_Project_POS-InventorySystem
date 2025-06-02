<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$current = basename($_SERVER['PHP_SELF']);

// Connect to DB
$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

/// Fetch all sales with user info, most recent first (by date and id)
$sales = [];
$search_invoice = '';
if (isset($_GET['search_invoice']) && !empty(trim($_GET['search_invoice']))) {
    $search_invoice = $mysqli->real_escape_string(trim($_GET['search_invoice']));
    $sql = "SELECT sales.id AS sale_id, sales.invoice_number, users.username, sales.sale_date, sales.total 
            FROM sales 
            LEFT JOIN users ON sales.user_id = users.id 
            WHERE sales.invoice_number LIKE '%$search_invoice%'
            ORDER BY sales.sale_date DESC, sales.id DESC";
} else {
    $sql = "SELECT sales.id AS sale_id, sales.invoice_number, users.username, sales.sale_date, sales.total 
            FROM sales 
            LEFT JOIN users ON sales.user_id = users.id 
            ORDER BY sales.sale_date DESC, sales.id DESC";
}

$result = $mysqli->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
}

// Fetch sale details if a sale is selected
$sale_details = [];
$sale = null; // Initialize sale variable
if (isset($_GET['sale_id'])) {
    $sale_id = intval($_GET['sale_id']);
    $stmt = $mysqli->prepare("SELECT sd.*, p.name AS product_name, p.image 
                              FROM sales_details sd 
                              LEFT JOIN products p ON sd.product_id = p.id 
                              WHERE sd.sale_id = ?");
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sale_details[] = $row;
    }
    $stmt->close();

    // Fetch the sale information for the selected sale_id
    $sale_stmt = $mysqli->prepare("SELECT sales.invoice_number, users.username, sales.sale_date, sales.total
                               FROM sales 
                               LEFT JOIN users ON sales.user_id = users.id 
                               WHERE sales.id = ?");
    $sale_stmt->bind_param("i", $sale_id);
    $sale_stmt->execute();
    $sale_result = $sale_stmt->get_result();
    $sale = $sale_result->fetch_assoc(); // Get the sale details
    $sale_stmt->close();
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Sales - Mia's Bags</title>
  <link rel="stylesheet" href="./assets/style.css">
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2 class="brand"> Mia's Bags</h2>
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
          <h2>Sales</h2>

          <form method="GET" class="search-form">
          <input
            type="text"
            name="search_invoice"
            placeholder="Search Invoice Number..."
            value="<?= isset($_GET['search_invoice']) ? htmlspecialchars($_GET['search_invoice']) : '' ?>"
            class="search-input"
          />
          <button type="submit" class="search-button">Search</button>
        </form>

        <table class="sales-table">
          <thead>
            <tr>
              <th>Invoice#</th>
              <th>Cashier</th>
              <th>Date</th>
              <th>Total</th>
              <th>Details</th>
              <?php if ($role === 'admin'): ?>
                <th>Action</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sales as $sale_row): ?>
              <tr>
                <td><?= htmlspecialchars($sale_row['invoice_number']) ?></td>
                <td><?= htmlspecialchars($sale_row['username']) ?></td>
                <td><?= htmlspecialchars($sale_row['sale_date']) ?></td>
                <td>₱<?= number_format($sale_row['total'], 2) ?></td>
                <td>
                   <button type="button" class="btn-details" onclick="window.location='sales.php?sale_id=<?= $sale_row['sale_id'] ?>'">View</button>
                </td>
                <?php if ($role === 'admin'): ?>
                <td>
                  <button type="button" class="btn-delete" onclick="if(confirm('Are you sure you want to delete this sale?')) window.location='delete_sale.php?id=<?= $sale_row['sale_id'] ?>';">Delete</button>
                </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if (!empty($sale_details) && $sale): ?>
        <div id="saleModal" class="modal" style="display:block;">
          <div class="modal-content">
            <span class="close" onclick="window.location='sales.php'">&times;</span>
            <h3 class="modal-title">Receipt</h3>
            <div id="receipt-area">
                <p><strong>Invoice#:</strong> <?= htmlspecialchars($sale['invoice_number']) ?></p>
                <p><strong>Cashier:</strong> <?= htmlspecialchars($sale['username']) ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($sale['sale_date']) ?></p>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sale_details as $detail): ?>
                        <tr>
                            <td>
                                <img src="<?= !empty($detail['image']) ? './assets/images/' . htmlspecialchars($detail['image']) : './assets/images/default_product.png' ?>"
                                alt="<?= htmlspecialchars($detail['product_name']) ?>"
                                style="width:32px;height:32px;object-fit:cover;border-radius:6px;">
                            </td>
                            <td><?= $detail['quantity'] ?></td>
                            <td>₱<?= number_format($detail['selling_price'], 2) ?></td>
                            <td>₱<?= number_format($detail['quantity'] * $detail['selling_price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="modal-total"><strong>Total:</strong> ₱<?= number_format($sale['total'], 2) ?></p>
            </div>
            <div class="modal-actions">
                <button onclick="printReceipt()">Print Receipt</button>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
  <script>
    function printReceipt() {
      var printContents = document.getElementById('receipt-area').innerHTML;
      var originalContents = document.body.innerHTML;
      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      window.location.href = 'sales.php'; // Redirect after printing
    }
  </script>
</body>
</html>
