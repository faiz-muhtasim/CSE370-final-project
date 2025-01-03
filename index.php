<?php
session_start();
include("database.php");

if (isset($_POST["login"])) {

    // Check if both fields are filled
    if (!empty($_POST["username"]) && !empty($_POST["password"])) {

        // Get the entered username and password
        $entered_username = $_POST["username"];
        $entered_password = $_POST["password"];

        // SQL query to fetch user data based on username
        $sql = "SELECT * FROM user WHERE ID = '$entered_username'";  // WARNING: SQL Injection risk here

        $result = $conn->query($sql);

        // Check if the user exists
        if ($result->num_rows > 0) {
            // Fetch the user record
            $row = $result->fetch_assoc();

            // Compare the entered password with the stored password
            if ($entered_password == $row['password']) {
                // Store the username and password in session variables
                $_SESSION["username"] = $entered_username;
                $_SESSION["password"] = $entered_password;

                // Redirect to home.php if login is successful
                header("Location: home.php");
                exit();  // Always exit after redirect
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "Invalid username.";
        }
    } else {
        $error_message = "Please enter both username and password.";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esho Kichu Khai - Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 300px;
            text-align: center;
        }
        .login-container h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        .login-container input[type="text"], .login-container input[type="password"] {
            width: 100%;
            padding: 10px 15px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            background-color: #ff6600;
            color: white;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #e65c00;
        }
        .register-link, .guest-login {
            display: block;
            margin-top: 10px;
            color: #333;
            text-decoration: none;
        }
        .register-link:hover, .guest-login:hover {
            text-decoration: underline;
        }
        .note {
            margin-top: 20px;
            font-size: 12px;
            color: #888;
        }
        .error-message {
            color: #e74c3c;
            background-color: #f8d7da;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h1>Esho Kichu Khai</h1>
    <form action="index.php" method="POST">
        <input type="text" name="username" placeholder="Enter ID" required>
        <input type="password" name="password" placeholder="Enter Password" required>
        <button type="submit" name="login" value="login">Login</button>
    </form>

    <!-- Display error message if any -->
    <?php if (isset($error_message)) { ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php } ?>

    <a href="test.php" class="register-link">Register to oder Food!</a>
    <!-- <a href="#" class="guest-login">Guest Login</a>
    <div class="note">Guest login doesn't require an account.</div> -->
</div>

</body>
</html>
