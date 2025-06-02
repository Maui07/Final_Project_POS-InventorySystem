<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$current = basename($_SERVER['PHP_SELF']);

// Connect to DB (update with your credentials)
$mysqli = new mysqli("localhost", "root", "", "project02");
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Fetch all users
$result = $mysqli->query("SELECT id, username, role FROM users ORDER BY id ASC");
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Management - Mia's Bags</title>
  <link rel="stylesheet" href="./assets/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto&display=swap" rel="stylesheet">
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
        <h2>User Management</h2>
        <button class="btn-add" onclick="resetForm()">Add New User</button>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th> 
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="user-table-body">
            <?php foreach ($users as $user): ?>
              <tr data-id="<?= $user['id'] ?>" data-username="<?= htmlspecialchars($user['username']) ?>" data-role="<?= $user['role'] ?>">
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['role']) ?></td>
                <td>
                  <button class="btn-edit" onclick="editUser(<?= $user['id'] ?>)">Edit</button>
                  <button class="btn-delete" onclick="deleteUser(<?= $user['id'] ?>)">Delete</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

<div id="userModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeModal">&times;</span>
    <h3 id="modal-title">Add User</h3>
    <form id="modal-user-form" method="POST" action="create_user.php">
      <input type="hidden" name="id" id="modal-user-id" value="" />
      <div class="form-group">
        <label for="modal-username">Username</label>
        <input type="text" id="modal-username" name="username" required />
      </div>
      <div class="form-group">
        <label for="modal-password" id="modal-password-label">Password</label>
        <input type="password" id="modal-password" name="password" required />
        <span id="password-note" class="password-note" style="display:none;">(leave blank to keep current password)</span>
      </div>
      <div class="form-group">
        <label for="modal-role">Role</label>
        <select id="modal-role" name="role" required>
          <option value="" disabled selected>Select Role</option>
          <option value="admin">Admin</option>
          <option value="cashier">Cashier</option>
        </select>
      </div>
      <input type="submit" value="Add User" />
    </form>
  </div>
</div>
      </section>
    </main>
  </div>
  <script src="./assets/js/user_management.js"></script>
</body>
</html>