<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['product_id'];
    $name = $_POST['product_name'];
    $price = $_POST['product_price'];
    $stock = $_POST['product_stock'];
    $category_id = intval($_POST['product_category']);

    // Get existing image filename from DB
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($existingImage);
    $stmt->fetch();
    $stmt->close();

    $imageName = $existingImage; // Default to existing image

    if (!empty($_FILES['product_image']['name'])) {
        // Generate unique filename for uploaded image
        $imageName = time() . '_' . basename($_FILES['product_image']['name']);
        $targetPath = './assets/images/' . $imageName;

        if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
            die('Image upload failed.');
        }

        // Delete old image file if exists
        if (!empty($existingImage) && file_exists("./assets/images/" . $existingImage)) {
            unlink("./assets/images/" . $existingImage);
        }
    }

    // Update category_id instead of category
    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ?, category_id = ?, image = ? WHERE id = ?");
    $stmt->bind_param("sdissi", $name, $price, $stock, $category_id, $imageName, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: products.php");
    exit();
}
?>