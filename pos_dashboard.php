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
    die("Connection failed: " . $mysqli->connect_error);
}

// Fetch categories
$category_result = $mysqli->query("SELECT id, name FROM category ORDER BY name ASC");
$categories = [];
if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row;
    }
}


// Fetch products (with image and category if available)
$product_result = $mysqli->query("
    SELECT p.id, p.name, p.stock, p.price, p.image, p.category_id, c.name AS category_name
    FROM products p
    LEFT JOIN category c ON p.category_id = c.id
    ORDER BY p.name ASC
");
$products = [];
if ($product_result) {
    while ($row = $product_result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>POS System - Mia's Bags</title>
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
        <h2>POS System</h2>

        <!-- Search Bar -->
        <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by product name" onkeyup="filterProducts()">
        </div>


        <!-- Category Selection -->
        <div class="category-filter">
        <label for="categorySelect"></label>
        <select id="categorySelect" onchange="filterByCategory()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option> 
            <?php endforeach; ?>
        </select>
        </div>

        <form method="POST" action="process_sale.php" id="saleForm">
        
            <div class="product-grid-wrapper">
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
            <?php if ($product['stock'] > 0): // Only display products with stock ?>
                <div class="product-card"
                    data-id="<?= $product['id'] ?>"
                    data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"
                    data-price="<?= $product['price'] ?>"
                    data-stock="<?= $product['stock'] ?>"
                    data-category="<?= $product['category_id'] ?>"
                    data-image="<?= !empty($product['image']) ? './assets/images/' . htmlspecialchars($product['image'], ENT_QUOTES) : './assets/images/default_product.png' ?>">
                    <img src="<?= !empty($product['image']) ? './assets/images/' . htmlspecialchars($product['image']) : './assets/images/default_product.png' ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                    <div class="product-stock">Stock: <span><?= $product['stock'] ?></span></div>
                    <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                    <div class="product-actions">
                        <input type="number" min="1" max="<?= $product['stock'] ?>" value="" class="qty-input" placeholder="Quantity">
                        <button type="button" class="add-btn" onclick="addToSale(this)">Add</button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
            </div>
            </div>
            <h3>Checkout List</h3>
            <table id="sale-items-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="sale-items"></tbody>
            </table>
            <label>Total ₱: <input type="number" name="total" id="sale-total" step="0.01" readonly required></label><br><br>
            <input type="submit" value="Save Sale">
        </form>

        <?php
// Show modal receipt if sale was successful
if (isset($_GET['success']) && isset($_GET['sale_id'])):
    $sale_id = intval($_GET['sale_id']);
    $sale_stmt = $mysqli->prepare("SELECT s.id, s.invoice_number, s.sale_date, s.total, u.username 
                                    FROM sales s 
                                    LEFT JOIN users u ON s.user_id = u.id 
                                    WHERE s.id = ?");
    $sale_stmt->bind_param("i", $sale_id);
    $sale_stmt->execute();
    $sale_result = $sale_stmt->get_result();
    $sale = $sale_result->fetch_assoc();
    $sale_stmt->close();

    // Check if sale exists
    if ($sale) {
        $invoice_number = htmlspecialchars($sale['invoice_number']);
    } else {
        $invoice_number = 'N/A'; // Default value if no sale found
    }

    // Fetch sale details
    $details_stmt = $mysqli->prepare("SELECT sd.quantity, sd.selling_price, p.name, p.image 
                                       FROM sales_details sd 
                                       LEFT JOIN products p ON sd.product_id = p.id 
                                       WHERE sd.sale_id = ?");
    $details_stmt->bind_param("i", $sale_id);
    $details_stmt->execute();
    $details_result = $details_stmt->get_result();
    $details = [];
    while ($row = $details_result->fetch_assoc()) {
        $details[] = $row;
    }
    $details_stmt->close();
?>
<div id="saleModal" class="modal" style="display:block;">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h3 class="modal-title">Receipt</h3>
    <div id="receipt-area">
        <p><strong>Invoice#:</strong> <?= $invoice_number ?></p>
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
                <?php foreach ($details as $item): ?>
                <tr>
                    <td>
                        <img src="<?= !empty($item['image']) ? './assets/images/' . htmlspecialchars($item['image']) : './assets/images/default_product.png' ?>"
                        alt="<?= htmlspecialchars($item['name']) ?>"
                        style="width:32px;height:32px;object-fit:cover;border-radius:6px;">
                    </td>
                    <td><?= $item['quantity'] ?></td>
                    <td>₱<?= number_format($item['selling_price'], 2) ?></td>
                    <td>₱<?= number_format($item['quantity'] * $item['selling_price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="modal-total"><strong>Total:</strong> ₱<?= number_format($sale['total'], 2) ?></p>
    </div>
    <div class="modal-actions">
        <button onclick="printReceipt()">Print Receipt</button>
        <a href="pos_dashboard.php" class="btn-cancel">New Sale</a>
    </div>
  </div>
</div>
<?php endif; ?>

        <hr>

        <?php
        // Fetch previous sales for this cashier, most recent first (accurate ordering)
        $prev_sales_stmt = $mysqli->prepare(
            "SELECT s.id, s.sale_date, s.total 
             FROM sales s 
             LEFT JOIN users u ON s.user_id = u.id 
             WHERE u.username = ? 
             ORDER BY s.sale_date DESC, s.id DESC "
        );
        $prev_sales_stmt->bind_param("s", $username);
        $prev_sales_stmt->execute();
        $prev_sales_result = $prev_sales_stmt->get_result();
        ?>

        <h3>Previous Sales</h3>
        <table>
            <thead>
                <tr>
                    <th>Sale ID</th>
                    <th>Date</th>
                    <th>Total (₱)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $prev_sales_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['sale_date']) ?></td>
                    <td><?= number_format($row['total'], 2) ?></td>
                    <td>
                        <a href="pos_dashboard.php?success=1&sale_id=<?= $row['id'] ?>" class="btn-add-view">View</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php $prev_sales_stmt->close(); ?>
      </section>
    </main>
  </div>
  <script>
    function filterProducts() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const categoryId = document.getElementById('categorySelect').value;
        const productCards = document.querySelectorAll('.product-card');

        productCards.forEach(card => {
            const name = card.getAttribute('data-name').toLowerCase();
            const category = card.getAttribute('data-category');

            const matchName = name.includes(searchTerm);
            const matchCategory = !categoryId || category === categoryId;

            if (matchName && matchCategory) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function filterByCategory() {
        // Reuse the same logic as search for consistency
        filterProducts();
    }
    </script>
  <script src="./assets/js/cashier.js"></script>
</body>
</html>