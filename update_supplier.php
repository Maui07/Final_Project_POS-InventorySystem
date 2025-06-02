<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['supplier_id'];
    $name = $_POST['supplier_name'];
    $contact_person = $_POST['supplier_contact_person'];
    $phone = $_POST['supplier_phone'];
    $email = $_POST['supplier_email'];
    $address = $_POST['supplier_address'];

    $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=?");
    $stmt->bind_param("sssssi", $name, $contact_person, $phone, $email, $address, $id);

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