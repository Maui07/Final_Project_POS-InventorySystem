<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Mia's Bags</title>
  <link rel="stylesheet" href="./assets/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Roboto&display=swap" rel="stylesheet">
</head>
<body class="login-page">
  <div class="login-container">
    <h1 class="title"> Mia's Bags</h1>
    <form action="auth.php" method="POST" class="login-form">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" placeholder="Enter your username" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter your password" required>

      <label for="role">Select Role</label>
      <select id="role" name="role" required>
        <option value="" disabled selected>Select your role</option>
        <option value="admin">Admin</option>
        <option value="cashier">Cashier</option>
      </select>

      <button type="submit">Log In</button>
    </form>
  </div>
</body>
</html>