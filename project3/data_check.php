<?php
session_start();
include 'includes/connect.php';


if(isset($_POST['apply']))
{
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $ok = db_execute(
        $data,
        "INSERT INTO admission (name, email, phone, message) VALUES (?, ?, ?, ?)",
        [$name, $email, $phone, $message]
    );

    if($ok)
    {
        $_SESSION['message'] = "your application has been created successfully";
        header("Location: index.php");
    }
    else
    {
        echo "Error creating application.";
    }
}
?>