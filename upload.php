<?php
session_start();
require_once "config.php";

// Check if the user is logged in, if not then redirect him to the login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Function to get file extension
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Define upload directory
$targetDir = "uploads/";

// Initialize variables
$uploadOk = 1;
$imageFileType = getFileExtension($_FILES["fileToUpload"]["name"]);
$targetFile = $targetDir . uniqid() . "." . $imageFileType;
$username = $_SESSION["username"];

// Check if image file is a valid image
if (isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check === false) {
        echo "File is not an image.";
        $uploadOk = 0;
    }
}

// Check file size
if ($_FILES["fileToUpload"]["size"] > 5000000) { // 5MB limit
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
}

// Check if file already exists
if (file_exists($targetFile)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}

// Allow certain file formats
$allowedExtensions = array("jpg", "jpeg", "png", "gif");
if (!in_array($imageFileType, $allowedExtensions)) {
    echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
} else {
    // Attempt to upload file
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
        // Update the database with the image path
        $sql = "UPDATE users SET image_path = ? WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $targetFile, $username);
            if (mysqli_stmt_execute($stmt)) {
                echo "The file " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " has been uploaded.";
                header("location: welcome.php");
                exit; // Make sure to exit after redirection
            } else {
                echo "Sorry, there was an error updating your record.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}

mysqli_close($link);
?>
