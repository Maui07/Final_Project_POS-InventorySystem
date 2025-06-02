<?php
session_start();

function createOrUpdateUser($mysqli, $username, $password, $role) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    // Check if user exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        // Insert new user
        $stmt_insert = $mysqli->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $username, $hashed, $role);
        $stmt_insert->execute();
        $stmt_insert->close();
    } else {
        // Update password and role
        $stmt_update = $mysqli->prepare("UPDATE users SET password = ?, role = ? WHERE username = ?");
        $stmt_update->bind_param("sss", $hashed, $role, $username);
        $stmt_update->execute();
        $stmt_update->close();
    }
    $stmt->close();
}

// DB connection (MySQLi)
$mysqli = new mysqli("localhost", "root", "",  "project02");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

createOrUpdateUser($mysqli, "admin", "admin123", "admin");
createOrUpdateUser($mysqli, "cashier", "cashier123", "cashier");

$mysqli->close();

// PDO connection for login
$host = 'localhost';
$db   = 'project02';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=$port";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Login function
function login($pdo, $username, $password, $role) {
    if (empty($username) || empty($password) || empty($role)) {
        echo "<script>alert('All fields are required.'); window.history.back();</script>";
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; 
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        if ($user['role'] === 'admin') {
            header("Location: inventory_dashboard.php");
            exit;
        } elseif ($user['role'] === 'cashier') {
            header("Location: pos_dashboard.php");
            exit;
        } else {
            echo "<script>alert('Unknown role.'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('Invalid credentials.'); window.history.back();</script>";
        exit;
    }
}

// Retrieve form data and login
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? '';
login($pdo, $username, $password, $role);
?>