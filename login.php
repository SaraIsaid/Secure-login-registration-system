<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect them to the appropriate page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if(isset($_SESSION["admin"]) && $_SESSION["admin"] === true){
        header("location: admin.php");
    } else {
        header("location: welcome.php");
    }
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Check if reCAPTCHA response is present in the form submission
    if(isset($_POST['g-recaptcha-response'])) {
        $recaptcha_response = $_POST['g-recaptcha-response'];

        // Your reCAPTCHA secret key
        $recaptcha_secret_key = "6Lc8lKopAAAAANb1EiFrl0z9YJa8Af3RUg_AiX4R";

        // URL for reCAPTCHA verification
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

        // Data to be sent for reCAPTCHA verification
        $recaptcha_data = [
            'secret'   => $recaptcha_secret_key,
            'response' => $recaptcha_response
        ];

        // Configure the request
        $recaptcha_options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($recaptcha_data)
            ]
        ];

        // Create a stream context
        $recaptcha_context  = stream_context_create($recaptcha_options);

        // Make a request to the reCAPTCHA verification endpoint
        $recaptcha_result = file_get_contents($recaptcha_url, false, $recaptcha_context);

        // Decode the JSON response
        $recaptcha_jsonArray = json_decode($recaptcha_result, true);

        // Check if reCAPTCHA verification was successful
        if(!$recaptcha_jsonArray['success']) {
            $login_err = "reCAPTCHA verification failed. Please try again.";
        }
    }

    // Validate credentials
    if(empty($username_err) && empty($password_err) && empty($login_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password, admin FROM users WHERE username = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);

                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $admin);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["admin"] = $admin; // Set admin status in session

                            // Redirect user to 2FA page
                            header("location: twofa.php");
                            exit;
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 360px; padding: 20px; }
    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="wrapper">
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="6Lc8lKopAAAAAJkKp8HepUtbMmYuLNgZaz5UinC3"></div>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
            <p>Forgot your password? <a href="forgot_password.php">Reset it here</a>.</p>
        </form>
    </div>
</body>
</html>
