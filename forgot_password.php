<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once('C:\Users\user\vendor\autoload.php');
require_once "config.php";

// Check if form is submitted
if(isset($_POST["username"])) {
    // Get username from form
    $username = $_POST["username"];

    // Prepare a statement to check if username exists
    $sql = "SELECT email FROM users WHERE username = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind parameters
        mysqli_stmt_bind_param($stmt, "s", $username);

        // Execute the statement
        mysqli_stmt_execute($stmt);

        // Store result
        mysqli_stmt_store_result($stmt);

        if(mysqli_stmt_num_rows($stmt) > 0) {
            // Bind result variable
            mysqli_stmt_bind_result($stmt, $email);

            // Fetch the result
            mysqli_stmt_fetch($stmt);
            
            // Generate a random token
            $bytes = random_bytes(32);
            $token = bin2hex($bytes);
            
            // Insert token into database
            $sql_token = "INSERT INTO password_token (user, email, token, created, status) VALUES (?, ?, ?, NOW(), 1)";
            if ($stmt_token = mysqli_prepare($link, $sql_token)) {
                // Bind parameters
                mysqli_stmt_bind_param($stmt_token, "sss", $username, $email, $token);
                
                // Execute the statement
                mysqli_stmt_execute($stmt_token);

                // Close statement
                mysqli_stmt_close($stmt_token);
            }

            // Sender email address
            $from = "sarani28@outlook.com"; // Your email address

            // Email subject and message
            $subject = "Reset Password";
            $message = "Hello $username,\n\nYou recently requested to reset your password for your account. Click the link below to reset it:\n\nReset Password: http://localhost/projectwebsecure/reset.php?token=$token\n\nIf you did not request a password reset, please ignore this email or contact our support team.\n\nBest regards,\nYour Company Name";

            // Email headers
            $headers = "From: $from\r\n";
            $headers .= "Reply-To: $from\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            // SMTP server configuration
            $smtpServer = "smtp-mail.outlook.com";
            $smtpPort = 587;
            $smtpUsername = "sarani28@outlook.com"; // Your SMTP username
            $smtpPassword = "1+x==x=false"; // Your SMTP password
            
            // Create a new PHPMailer instance
            $mail = new PHPMailer();

            // Set SMTP configuration
            $mail->isSMTP();
            $mail->Host = $smtpServer;
            $mail->Port = $smtpPort;
            $mail->SMTPSecure = 'tls';
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;

            // Set email parameters
            $mail->setFrom($from);
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->Body = $message;

            // Send the email
            if ($mail->send()) {
                echo "Email sent successfully!";
            } else {
                echo "Error: " . $mail->ErrorInfo;
            }
            echo "If the username is correct, an email will be sent to the corresponding email";
        } else {
            echo "No account found with that username.";
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }

    // Close connection
    mysqli_close($link);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forget Password</title>
</head>
<body>
    <h2>Forget Password</h2>
    <form action="forgot_password.php" method="post">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
