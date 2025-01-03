<?php
include("database.php"); // Assuming your database connection file is named database.php

// Initialize variables for error handling, success message, and user ID
$errors = [];
$success_message = "";
$user_id = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user inputs
    $phone_no = $_POST['phone_no'];
    $password = $_POST['password'];
    $address = $_POST['address'];
    $date_of_birth = $_POST['date_of_birth'];

    // Basic validation
    if (empty($phone_no)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }
    if (empty($date_of_birth)) {
        $errors[] = "Date of birth is required.";
    }

    // If no validation errors, proceed with inserting into the database
    if (count($errors) == 0) {
        // Hash the password for security
        // $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Prepare SQL to insert new customer
        $sql = "INSERT INTO user (phone_no, password, address, date_of_birth, type) 
                VALUES ('$phone_no', '$password', '$address', '$date_of_birth', 'customer')";

        // Execute the query and check if the insert was successful
        if ($conn->query($sql) === TRUE) {
            // Get the last inserted ID (user ID)
            $user_id = $conn->insert_id;
            $success_message = "Registration successful! Your User ID is: " . $user_id;
        } else {
            $errors[] = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration</title>
    <style>
        /* Applying the same theme as your homepage */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .form-container {
            width: 400px;
            margin: 50px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .submit-btn {
            background-color: #ff6600;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #ff8533;
        }

        .home-btn {
            background-color: #008cba;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        .home-btn:hover {
            background-color: #00a1d1;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }

        .success {
            color: green;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Customer Registration</h2>

    <!-- Display errors if any -->
    <?php if (count($errors) > 0): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Display success message if registration is successful and show user ID -->
    <?php if (!empty($success_message)): ?>
        <div class="success">
            <p><?php echo $success_message; ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="phone_no">Phone Number</label>
            <input type="text" id="phone_no" name="phone_no" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" required>
        </div>

        <div class="form-group">
            <label for="date_of_birth">Date of Birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth" required>
        </div>

        <button type="submit" class="submit-btn">Register</button>
    </form>

    <!-- Button to go to index.php -->
    <form action="index.php">
        <button type="submit" class="home-btn">Go to Home Page</button>
    </form>
</div>

</body>
</html>
