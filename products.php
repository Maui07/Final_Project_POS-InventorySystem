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

// Fetch all products
$result = $mysqli->query("
    SELECT p.id, p.name, p.price, p.stock, p.image, p.category_id, c.name AS category_name
    FROM products p
    LEFT JOIN category c ON p.category_id = c.id
    ORDER BY p.id ASC
");

$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}


$cat_result = $mysqli->query("SELECT id, name FROM category ORDER BY name ASC");
$categories = [];
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Products - Mia's Bags</title>
  <link rel="stylesheet" href="./assets/style.css">
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
          <a href="pos_dashboard.php" class="<?= $current === 'pos_dashboard.php' ? 'active' : '' ?>">POS</a>
          <a href="sales.php" class="<?= $current === 'sales.php' ? 'active' : '' ?>">Sales</a>
        <?php endif; ?>
      </nav>
      <div class="admin-actions">
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
    </aside>
    <main class="main-content">
      <section class="content">
          <h2>Products</h2>
          <button class="btn-add" onclick="resetProductForm()">Add Product</button>
          <button class="btn-add" onclick="openCategoryModal()">Add Category</button>
        <table class="products-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Category</th>
              <th>Image</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product): ?>
              <tr
                data-id="<?= $product['id'] ?>"
                data-name="<?= htmlspecialchars($product['name']) ?>"
                data-price="<?= $product['price'] ?>"
                data-stock="<?= $product['stock'] ?>"
                data-category="<?= $product['category_id'] ?>"
                data-image="<?= htmlspecialchars($product['image']) ?>"
              >
                  <td><?= $product['id'] ?></td>
                  <td><?= htmlspecialchars($product['name']) ?></td>
                  <td>₱<?= number_format($product['price'], 2) ?></td>
                  <td><?= $product['stock'] ?></td>
                  <td><?= htmlspecialchars($product['category_name']) ?></td>
                  <td>
                    <?php if (!empty($product['image'])): ?>
                      <img src="./assets/images/<?= htmlspecialchars($product['image']) ?>" alt="Product Image" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                    <?php else: ?>
                      <span style="color:#bbb;">No image</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <button class="btn-edit" onclick="editProduct(<?= $product['id'] ?>)">Edit</button>
                    <button class="btn-delete" onclick="deleteProduct(<?= $product['id'] ?>)">Delete</button>
                  </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Product Modal -->
        <div id="productModal" class="modal">
          <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h3 id="modal-title">Add Product</h3>
            <form id="modal-product-form" method="POST" action="create_product.php" enctype="multipart/form-data">
              <input type="hidden" name="product_id" id="modal-product-id" value="" />
              <div class="form-group">
                <label for="modal-product-name">Name</label>
                <input type="text" id="modal-product-name" name="product_name" required />
              </div>
              <div class="form-group">
                <label for="modal-product-price">Price</label>
                <input type="number" id="modal-product-price" name="product_price" min="0" step="0.01" required />
              </div>
              <div class="form-group">
                <label for="modal-product-stock">Stock</label>
                <input type="number" id="modal-product-stock" name="product_stock" min="0" required />
              </div>
              <div class="form-group">
                <label for="modal-product-category">Category</label>
                <select id="modal-product-category" name="product_category" required>
                    <option value="" disabled selected>Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="modal-product-image">Image</label>
                <input type="file" id="modal-product-image" name="product_image" accept="image/*" />
                <img id="image-preview" src="" alt="Preview" style="display:none;width:80px;height:80px;margin-top:8px;object-fit:cover;border-radius:6px;">
              </div>
              <div class="modal-form-buttons">
             <input type="submit" value="Save" />
             <button type="button" onclick="document.getElementById('productModal').style.display='none'">Cancel</button>
            </div>
            </form>
          </div>
        </div>

          <!-- Category Modal -->
          <div id="categoryModal" class="modal">
            <div class="modal-content">
              <span class="close" onclick="closeCategoryModal()">&times;</span>
              <h3 id="category-modal-title">Add Category</h3>
              <form id="category-form" method="POST" action="category.php">
                <input type="hidden" name="category_id" id="category-id" value="">
                <div class="form-group">
                  <label for="category-name">Category Name</label>
                  <input type="text" id="category-name" name="category_name" required>
                </div>
                <div class="modal-form-buttons">
                  <input type="submit" name="save_category" value="Save"> 
                  <button type="button" onclick="closeCategoryModal()">Cancel</button>
                </div>
              </form>
              <hr>
              <h4>Existing Categories</h4>
              <ul id="category-list">
                <?php foreach ($categories as $cat): ?>
                  <li>
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                    <button type="button" class="edit-btn" onclick="editCategory('<?= $cat['id'] ?>', '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>')">Edit</button>
                    <button type="button"  class="delete-btn" onclick="deleteCategory('<?= $cat['id'] ?>')">Delete</button>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
      </section>
    </main>
  </div>
  <script src="./assets/js/product.js"></script>
  <script src="./assets/js/category.js"></script>
</body>
</html>