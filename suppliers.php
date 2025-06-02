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

// Fetch all suppliers
$result = $mysqli->query("SELECT id, name, contact_person, phone, email, address FROM suppliers ORDER BY id ASC");
$suppliers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Suppliers - Mia's Bags</title>
  <link rel="stylesheet" href="./assets/style.css">
</head>
<body>
  <di class="dashboard">
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
          <h2>Suppliers</h2>
          <button class="btn-add" onclick="resetSupplierForm()">Add Supplier</button>
        <table class="suppliers-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Contact Person</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Address</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($suppliers as $supplier): ?>
              <tr
                data-id="<?= $supplier['id'] ?>"
                data-name="<?= htmlspecialchars($supplier['name']) ?>"
                data-contact_person="<?= htmlspecialchars($supplier['contact_person']) ?>"
                data-phone="<?= htmlspecialchars($supplier['phone']) ?>"
                data-email="<?= htmlspecialchars($supplier['email']) ?>"
                data-address="<?= htmlspecialchars($supplier['address']) ?>"
              >
                <td><?= htmlspecialchars($supplier['name']) ?></td>
                <td><?= htmlspecialchars($supplier['contact_person']) ?></td>
                <td><?= htmlspecialchars($supplier['phone']) ?></td>
                <td><?= htmlspecialchars($supplier['email']) ?></td>
                <td><?= htmlspecialchars($supplier['address']) ?></td>
                <td>
                  <button class="btn-edit" onclick="editSupplier(<?= $supplier['id'] ?>)">Edit</button>
                  <button class="btn-delete" onclick="deleteSupplier(<?= $supplier['id'] ?>)">Delete</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Supplier Modal -->
        <div id="supplierModal" class="modal">
          <div class="modal-content">
            <span class="close" id="closeSupplierModal">&times;</span>
            <h3 id="supplier-modal-title">Add Supplier</h3>
            <form id="modal-supplier-form" method="POST" action="create_supplier.php">
              <input type="hidden" name="supplier_id" id="modal-supplier-id" value="" />
              <div class="form-group">
                <label for="modal-supplier-name">Name</label>
                <input type="text" id="modal-supplier-name" name="supplier_name" required />
              </div>
              <div class="form-group">
                <label for="modal-supplier-contact-person">Contact Person</label>
                <input type="text" id="modal-supplier-contact-person" name="supplier_contact_person" required />
              </div>
              <div class="form-group">
                <label for="modal-supplier-phone">Phone</label>
                <input type="text" id="modal-supplier-phone" name="supplier_phone" required />
              </div>
              <div class="form-group">
                <label for="modal-supplier-email">Email</label>
                <input type="email" id="modal-supplier-email" name="supplier_email" required />
              </div>
              <div class="form-group">
                <label for="modal-supplier-address">Address</label>
                <input type="text" id="modal-supplier-address" name="supplier_address" required />
              </div>
              <div class="modal-form-buttons">
                <input type="submit" value="Save" />
                <button type="button" onclick="document.getElementById('supplierModal').style.display='none'">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </main>
  </div>
    <script>
    document.getElementById('adminButton').addEventListener('click', function() {
      document.getElementById('adminDropdown').classList.toggle('hidden');
    });
  </script>
  <script src="./assets/js/supplier.js"></script>
</body>
</html>