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

// Generate invoice number function (unchanged)
function generateInvoiceNumber($mysqli) {
    $result = $mysqli->query("SELECT MAX(CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED)) AS max_invoice FROM purchases");
    $row = $result->fetch_assoc();
    $new_number = $row['max_invoice'] ? $row['max_invoice'] + 1 : 1;
    return 'INV-' . str_pad($new_number, 6, '982', STR_PAD_LEFT);
}

$invoice_number = generateInvoiceNumber($mysqli);

$search_invoice = isset($_GET['search_invoice']) ? $mysqli->real_escape_string($_GET['search_invoice']) : '';

$purchasesQuery = "
SELECT pur.id AS purchase_id, pur.invoice_number, pur.purchase_date, pur.status, sup.name AS supplier_name
FROM purchases pur
JOIN suppliers sup ON pur.supplier_id = sup.id
";

if (!empty($search_invoice)) {
    $purchasesQuery .= " WHERE pur.invoice_number LIKE '%$search_invoice%'";
}

$purchasesQuery .= " ORDER BY pur.purchase_date DESC, pur.id DESC";


$purchasesResult = $mysqli->query($purchasesQuery);
if (!$purchasesResult) {
    die("Query failed: (" . $mysqli->errno . ") " . $mysqli->error);
}

// Fetch all purchase details to group by purchase_id
$detailsQuery = "
SELECT purchase_id, product_id, quantity, cost_price, p.name AS product_name
FROM purchases_details pd
JOIN products p ON pd.product_id = p.id
";
$detailsResult = $mysqli->query($detailsQuery);
if (!$detailsResult) {
    die("Query failed: (" . $mysqli->errno . ") " . $mysqli->error);
}

// Organize purchase details by purchase_id for easy display
$purchaseDetails = [];
while ($row = $detailsResult->fetch_assoc()) {
    $purchaseDetails[$row['purchase_id']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Purchases - Mia's Bags</title>
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
      <?php elseif ($role === 'cashier'): ?>
        <a href="pos_dashboard.php" class="<?= $current === 'pos_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="sales.php" class="<?= $current === 'sales.php' ? 'active' : '' ?>">Sales</a>
      <?php endif; ?>
    </nav>
    <div class="admin-actions">
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </aside>

  <div class="main-content">
  <section class="content">
  <h2>Purchases</h2>
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


      <button id="openModalBtn" class="btn-add">Add Purchase</button>

      <form method="POST" action="post_purchase.php" onsubmit="return confirm('Are you sure you want to post selected purchases?');">
        <button type="submit" class="btn-add1" id="postBtn" disabled>POST</button>
        <table>
          <thead>
            <tr>
              <th></th>
              <th>Invoice#</th>
              <th>Supplier</th>
              <th>Products</th>
              <th>Quantity & Price</th>
              <th>Date</th>
              <th>Total Cost</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($purchase = $purchasesResult->fetch_assoc()): ?>
            <?php 
              $details = $purchaseDetails[$purchase['purchase_id']] ?? [];
              $totalCost = 0;
              foreach ($details as $d) {
                $totalCost += $d['quantity'] * $d['cost_price'];
              }
              $disabledCheckbox = $purchase['status'] ? 'disabled' : '';
            ?>
            <tr>
              <td>
                <input 
                  type="checkbox" 
                  name="selected_ids[]" 
                  value="<?= htmlspecialchars($purchase['purchase_id']) ?>" 
                  <?= $disabledCheckbox ?>
                >
              </td>
              <td><?= htmlspecialchars($purchase['invoice_number']) ?></td>
              <td><?= htmlspecialchars($purchase['supplier_name']) ?></td>
              <td>
                <?php foreach ($details as $d): ?>
                  <?= htmlspecialchars($d['product_name']) ?> <br>
                <?php endforeach; ?>
              </td>
              <td>
                <?php foreach ($details as $d): ?>
                  <div><?= (int)$d['quantity'] ?> x ₱<?= number_format($d['cost_price'], 2) ?></div>
                <?php endforeach; ?>
              </td>
              <td><?= htmlspecialchars($purchase['purchase_date']) ?></td>
              <td>₱<?= number_format($totalCost, 2) ?></td>
              <td><?= $purchase['status'] ? '<span style="color: green;">Posted</span>' : '<span style="color: red;">Pending</span>' ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </form>

      <?php if (isset($_GET['posted']) && $_GET['posted'] === 'success'): ?>
        <p class="success-message">Purchases successfully posted.</p>
      <?php endif; ?>

<!-- Modal -->
<div id="purchaseModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeModalBtn">&times;</span>
    <h3 id="supplier-modal-title">Add Purchase</h3>

    <form action="save_purchase.php" method="POST" id="purchaseForm">
      <label>Invoice Number:</label><br>
      <input type="text" name="invoice_number" value="<?= $invoice_number ?>" readonly required><br>
      <br><label>Supplier:</label>
      <select name="supplier_id" required>
        <option value="" disabled selected>Select Supplier</option>
        <?php
        $suppliers = $mysqli->query("SELECT id, name FROM suppliers ORDER BY name");
        while ($supplier = $suppliers->fetch_assoc()) {
            echo "<option value='{$supplier['id']}'>" . htmlspecialchars($supplier['name']) . "</option>";
        }
        ?>
      </select><br><br>

      <div id="purchases-container">
          <label>Product:</label><br>
          <button type="button" id="addPurchasesBtn" class="btn-addmore">Add More</button><br><br>
        <div class="purchases-row">
          <select name="products_id[]" required>
            <option value="" disabled selected>Select Product</option>
            <?php
            $products = $mysqli->query("SELECT id, name FROM products ORDER BY name");
            while ($product = $products->fetch_assoc()) {
                echo "<option value='{$product['id']}'>" . htmlspecialchars($product['name']) . "</option>";
            }
            ?>
          </select>
          <input type="number" name="quantity[]" min="1" placeholder="Quantity" required>
          <input type="number" step="0.01" name="cost_price[]" min="0" placeholder="Cost Price" required>
          <button type="button" class="btn-delete" title="Remove">Remove</button>
        </div>
      </div>
      <p>Total Cost: ₱<span id="totalCost">0.00</span></p>
      <div class="btn-container">
        <input type="submit" value="Save" class="btn-addBtn">
        <button type="button" id="cancelPurchaseBtn" class="btn-addBtn">Cancel</button>
      </div>
    </form>
    </div>
    </div>

     <!-- Hidden template for JS cloning -->
    <select id="purchase-template" style="display:none;">
      <option value="" disabled selected>Select Product</option>
      <?php
      $products = $mysqli->query("SELECT id, name FROM products ORDER BY name");
      while ($product = $products->fetch_assoc()) {
          echo "<option value='{$product['id']}'>" . htmlspecialchars($product['name']) . "</option>";
      }
      ?>
    </select>
</section>
</div>
</div>
<script>
// Enable/disable POST button depending on checkbox selection
const postBtn = document.getElementById('postBtn');
const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');

function togglePostBtn() {
    let anyChecked = false;
    checkboxes.forEach(chk => {
        if (chk.checked) anyChecked = true;
    });
    postBtn.disabled = !anyChecked;
}

checkboxes.forEach(chk => {
    chk.addEventListener('change', togglePostBtn);
});

// Initialize button state
togglePostBtn();
</script>
<script src="./assets/js/purchase.js"></script>
</body>
</html>
