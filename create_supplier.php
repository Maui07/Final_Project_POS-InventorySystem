<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['supplier_name'];
    $contact_person = $_POST['supplier_contact_person'];
    $phone = $_POST['supplier_phone'];
    $email = $_POST['supplier_email'];
    $address = $_POST['supplier_address'];

    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $contact_person, $phone, $email, $address);

    if ($stmt->execute()) {
        header("Location: suppliers.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>