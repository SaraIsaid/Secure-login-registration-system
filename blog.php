<?php
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Function to fetch and display posts
function displayPosts($link) {
    $query = "SELECT * FROM posts ORDER BY Published DESC";
    $result = mysqli_query($link, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
?>
            <div class="post">
                <h2><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="post-meta"><?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($row['Published'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><?= nl2br(htmlspecialchars($row['Post'], ENT_QUOTES, 'UTF-8')) ?></p>
            </div>
<?php
        }
    } else {
        echo "<div class='no-posts'>No Blog Posts Found</div>";
    }

    mysqli_free_result($result);
}

// Function to insert a new post
function insertPost($link, $username, $title, $content) {
    $query = "INSERT INTO posts (username, title, Post) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($link, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $username, $title, $content);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['UpdatedSucc'] = "Your post has been published!";
        } else {
            echo "Error: Could not execute the query: " . mysqli_error($link);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Error: Unable to prepare the SQL statement.";
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_post'])) {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);

    if (!empty($title) && !empty($content)) {
        insertPost($link, $_SESSION['username'], $title, $content);
    } else {
        echo "Both title and content are required to submit a post.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blogs</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .post {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .post h2 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #007bff;
        }
        .post-meta {
            font-size: 14px;
            color: #6c757d;
        }
        .no-posts {
            text-align: center;
            font-style: italic;
            color: #6c757d;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .new-post-heading {
            margin-top: 40px;
            margin-bottom: 20px;
            color: #007bff;
        }
        .form-container {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .btn-back {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Blogs</h2>
        <p>Read the latest posts or add a new one!</p>

        <!-- Display posts -->
        <div class="posts">
            <?php displayPosts($link); ?>
        </div>

        <!-- Form to add a new post -->
        <div class="form-container">
            <h3 class="new-post-heading">Add a New Post</h3>
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" class="form-control" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <input type="submit" name="new_post" class="btn btn-primary" value="Submit">
                </div>
            </form>
        </div>

        <!-- Button to redirect to the welcome page -->
        <div class="text-center btn-back">
            <a href="welcome.php" class="btn btn-secondary">Back to Welcome Page</a>
        </div>
    </div>
</body>
</html>
