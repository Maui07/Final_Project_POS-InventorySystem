<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['product_name'];
    $price = $_POST['product_price'];
    $stock = $_POST['product_stock'];
    $category_id = intval($_POST['product_category']);

    $imageName = '';
    if (!empty($_FILES['product_image']['name'])) {
        $imageName = time() . '_' . basename($_FILES['product_image']['name']);
        $targetPath = './assets/images/' . $imageName;

        if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
            die('Failed to upload image.');
        }
    }

    $stmt = $conn->prepare("INSERT INTO products (name, price, stock, category_id, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdiss", $name, $price, $stock, $category_id, $imageName);

    if ($stmt->execute()) {
        header("Location: products.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
    $uploaded_filename = basename($_FILES['product_image']['name']);
    $target_dir = './assets/images/';
    $target_file = $target_dir . $uploaded_filename;
    move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file);

    // Save $target_file or just $uploaded_filename in the database
    $image_path = $target_file; // or just $uploaded_filename
    // Use $image_path in your INSERT statement
}

    $stmt->close();
    $conn->close();
}
?>