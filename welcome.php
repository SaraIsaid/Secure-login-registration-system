<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$upload_err = "";
$image_path = "";

// Processing file upload when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["fileToUpload"])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check !== false) {
        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $upload_err = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $image_path = $target_file;

                // Update the database with the image path using parameterized query
                $sql = "UPDATE users SET image_path = ? WHERE username = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ss", $image_path, $_SESSION["username"]);
                    if (mysqli_stmt_execute($stmt)) {
                        // Image path updated successfully
                    } else {
                        $upload_err = "Sorry, there was an error updating your record.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $upload_err = "Sorry, there was an error updating your record.";
                }
            } else {
                $upload_err = "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        $upload_err = "File is not an image.";
    }
}
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font: 14px sans-serif; text-align: center; }
    </style>
</head>
<body>
    <h1 class="my-5">Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to our site.</h1>
    <p>
        <a href="change_email.php" class="btn btn-info">Change Email</a>
        <a href="logout.php" class="btn btn-danger ml-3">Sign Out of Your Account</a>
        <a href="blog.php" class="btn btn-primary ml-3">Go to Blog</a> <!-- New button to go to blog page -->
    </p>

    <!-- Upload and display image section -->
    <div class="my-5">
        <h2>Upload and Display Image</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <label for="fileToUpload">Select image to upload:</label>
            <input type="file" name="fileToUpload" id="fileToUpload" required>
            <input type="submit" value="Upload Image" name="submit" class="btn btn-primary">
        </form>

        <!-- Display the uploaded image -->
        <?php if ($image_path): ?>
            <h3>Your uploaded image:</h3>
            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Uploaded Image" class="img-fluid">
        <?php elseif ($upload_err): ?>
            <div class="alert alert-danger"><?php echo $upload_err; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
