<?php
session_start();
include("database.php");

// Check if the user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["username"];
$user_type = "";

// Fetch user type
$sql = "SELECT type FROM user WHERE ID = '$user_id'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_type = $row['type'];
}

// Handle cart functionality for customers
if ($user_type == 'customer') {
    if (isset($_POST['add_to_cart'])) {
        $food_name = $_POST['food_name'];
        $unit_ordered = $_POST['unit_ordered'];

        // Check if the food is available
        $check_stock_sql = "SELECT unit_available FROM food WHERE name = '$food_name'";
        $check_stock_result = $conn->query($check_stock_sql);
        $food = $check_stock_result->fetch_assoc();

        if ($food['unit_available'] >= $unit_ordered) {
            // Initialize cart if not already set
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Check if the food is already in the cart
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['food_name'] == $food_name) {
                    $item['unit_ordered'] += $unit_ordered; // Add to the existing quantity
                    $found = true;
                    break;
                }
            }

            // If food not found in cart, add it
            if (!$found) {
                $_SESSION['cart'][] = [
                    'food_name' => $food_name,
                    'unit_ordered' => $unit_ordered
                ];
            }

            // Reduce stock in the food table
            $new_stock = $food['unit_available'] - $unit_ordered;
            $update_stock_sql = "UPDATE food SET unit_available = $new_stock WHERE name = '$food_name'";
            $conn->query($update_stock_sql);
        } else {
            echo "Not enough stock available.";
        }
    }

    // Remove item from cart
    if (isset($_POST['remove_from_cart'])) {
        $food_name = $_POST['food_name'];

        // Loop through the cart to remove the item
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['food_name'] == $food_name) {
                // Restore the stock in the food table
                $restore_stock_sql = "SELECT unit_available FROM food WHERE name = '$food_name'";
                $restore_stock_result = $conn->query($restore_stock_sql);
                $food = $restore_stock_result->fetch_assoc();

                $new_stock = $food['unit_available'] + $item['unit_ordered'];
                $update_stock_sql = "UPDATE food SET unit_available = $new_stock WHERE name = '$food_name'";
                $conn->query($update_stock_sql);

                // Remove item from cart
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex the cart array
                break;
            }
        }
    }

    if (isset($_POST['place_order']) && isset($_SESSION['cart'])) {
        // Insert the order into the orders table
        foreach ($_SESSION['cart'] as $cart_item) {
            $food_name = $cart_item['food_name'];
            $unit_ordered = $cart_item['unit_ordered'];
    
            // Insert into orders table without specifying the order_id
            $insert_order_sql = "INSERT INTO orders (customer_id, food_name, unit_ordered, status, order_date) 
            VALUES ('$user_id', '$food_name', '$unit_ordered', 'pending', NOW())";

            if ($conn->query($insert_order_sql) === TRUE) {
                // Optionally handle success, like clearing the cart or showing a success message
                echo "Order placed successfully!";
            } else {
                // Handle error if the query fails
                echo "Error placing order: " . $conn->error;
            }
        }
    
        // Clear the cart after placing the order
        unset($_SESSION['cart']);
    }
}

// Admin adding/removing units to food
if ($user_type == 'admin' && isset($_POST['add_food_units'])) {
    $food_name = $_POST['food_name'];
    $additional_units = $_POST['additional_units'];

    // Update the food stock in the database
    $update_food_sql = "UPDATE food SET unit_available = unit_available + $additional_units WHERE name = '$food_name'";
    $conn->query($update_food_sql);
}

if ($user_type == 'admin' && isset($_POST['remove_food_units'])) {
    $food_name = $_POST['food_name'];
    $removal_units = $_POST['removal_units'];

    // Fetch current stock
    $current_stock_sql = "SELECT unit_available FROM food WHERE name = '$food_name'";
    $current_stock_result = $conn->query($current_stock_sql);
    $current_food = $current_stock_result->fetch_assoc();

    if ($removal_units <= $current_food['unit_available']) {
        // Update the food stock in the database if valid
        $update_food_sql = "UPDATE food SET unit_available = unit_available - $removal_units WHERE name = '$food_name'";
        $conn->query($update_food_sql);
    } else {
        echo "Cannot remove more units than available.";
    }
}

// Admin and Teller confirm order
if (($user_type == 'admin' || $user_type == 'teller') && isset($_POST['confirm_order'])) {
    $order_id = $_POST['order_id'];

    // Update the status of the order to 'served'
    $update_order_sql = "UPDATE orders SET status = 'served' WHERE order_id = '$order_id'";
    if ($conn->query($update_order_sql)) {
        echo "Order confirmed!";
    } else {
        echo "Error updating order: " . $conn->error;
    }
}

// Admin delete food item
if ($user_type == 'admin' && isset($_POST['delete_food'])) {
    $food_name = $_POST['food_name'];

    // First, delete all related orders
    $delete_orders_sql = "DELETE FROM orders WHERE food_name = '$food_name'";
    if ($conn->query($delete_orders_sql)) {
        // Now, delete the food item
        $delete_food_sql = "DELETE FROM food WHERE name = '$food_name'";
        if ($conn->query($delete_food_sql)) {
            echo "Food item and related orders deleted successfully!";
        } else {
            echo "Error deleting food item: " . $conn->error;
        }
    } else {
        echo "Error deleting related orders: " . $conn->error;
    }
}

// Admin add new food item
if ($user_type == 'admin' && isset($_POST['add_new_food'])) {
    $food_name = $_POST['food_name'];
    $unit_price = $_POST['unit_price'];
    $unit_available = $_POST['unit_available'];

    $insert_food_sql = "INSERT INTO food (name, unit_price, unit_available) VALUES ('$food_name', '$unit_price', '$unit_available')";
    if ($conn->query($insert_food_sql)) {
        echo "New food item added successfully!";
    } else {
        echo "Error adding food: " . $conn->error;
    }
}

// Fetch food items based on search and user type
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$base_sql = "SELECT * FROM food";
if ($user_type == 'customer') {
    $base_sql .= " WHERE unit_available > 0"; // Only available food for customers
}

if (!empty($search_term)) {
    if ($user_type == 'customer') {
        $food_sql = $base_sql . " AND name LIKE '%$search_term%'";
    } else {
        $food_sql = $base_sql . " WHERE name LIKE '%$search_term%'";
    }
} else {
    $food_sql = $base_sql;
}

$food_result = $conn->query($food_sql);

// Fetch all orders for admin and teller view
$orders_result = null;
if ($user_type == 'admin' || $user_type == 'teller') {
    $orders_sql = "SELECT o.*, f.unit_price, u.ID as user_id 
                  FROM orders o 
                  LEFT JOIN food f ON o.food_name = f.name 
                  LEFT JOIN user u ON o.customer_id = u.ID 
                  ORDER BY o.order_date DESC";

    // Admin: Filter orders by date
    if ($user_type == 'admin' && isset($_POST['filter_orders_by_date'])) {
        $order_date = $_POST['order_date'];
        $orders_sql = "SELECT o.*, f.unit_price, u.ID as user_id 
                       FROM orders o 
                       LEFT JOIN food f ON o.food_name = f.name 
                       LEFT JOIN user u ON o.customer_id = u.ID 
                       WHERE DATE(o.order_date) = '$order_date' 
                       ORDER BY o.order_date DESC";
    }

    $orders_result = $conn->query($orders_sql);
}

// Logout functionality
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esho Kichu Khai - Home</title>
    <style>
        .logout-btn {
            background-color: #ff0000;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
        }

        .logout-btn:hover {
            background-color: #cc0000;
        }

        .home-container {
            text-align: center;
            margin-bottom: 80px;
        }

        .food-item {
            display: inline-block;
            margin: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f4f4f4;
        }

        .food-item h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .food-item p {
            margin: 5px 0;
        }

        .orange-button {
            background-color: #ff6600;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .orange-button:hover {
            background-color: #ff8533;
        }

        .admin-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f8f8;
            border-radius: 8px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table th, .orders-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .orders-table th {
            background-color: #f4f4f4;
        }

        .orders-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .food-management {
            margin-top: 30px;
        }

        .food-management table {
            width: 100%;
            border-collapse: collapse;
        }

        .food-management th, .food-management td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .food-management th {
            background-color: #f4f4f4;
        }

        .remove-button {
            background-color: #ff4c4c;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .remove-button:hover {
            background-color: #ff0000;
        }
    </style>
</head>
<body>

<div class="home-container">
    <!-- Admin Food Management Section -->
    <?php if ($user_type == 'admin'): ?>
        <div class="food-management">
            <h2>Manage Food</h2>
            <table>
                <thead>
                    <tr>
                        <th>Food Name</th>
                        <th>Price (BDT)</th>
                        <th>Units Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($food = $food_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($food['name']); ?></td>
                            <td><?php echo htmlspecialchars($food['unit_price']); ?> BDT</td>
                            <td><?php echo htmlspecialchars($food['unit_available']); ?></td>
                            <td>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($food['name']); ?>">
                                    <input type="number" name="additional_units" placeholder="Add Units" required>
                                    <button type="submit" name="add_food_units" class="orange-button">Add</button>
                                </form>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($food['name']); ?>">
                                    <input type="number" name="removal_units" placeholder="Remove Units" required>
                                    <button type="submit" name="remove_food_units" class="remove-button">Remove</button>
                                </form>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($food['name']); ?>">
                                    <button type="submit" name="delete_food" class="remove-button">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Add new food item -->
            <div class="add-food">
                <h2>Add New Food</h2>
                <form method="POST" action="">
                    <input type="text" name="food_name" placeholder="Food Name" required>
                    <input type="number" name="unit_price" placeholder="Price (BDT)" required>
                    <input type="number" name="unit_available" placeholder="Units Available" required>
                    <button type="submit" name="add_new_food" class="orange-button">Add Food</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Food List Section -->
    <?php if ($user_type != 'teller'): // Teller should not see the available food ?>
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search food..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="orange-button">Search</button>
        </form>
        <div class="food-list">
            <?php while ($row = $food_result->fetch_assoc()): ?>
                <div class="food-item">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p>Price: <?php echo htmlspecialchars($row['unit_price']); ?> BDT</p>
                    <p>Units Available: <?php echo htmlspecialchars($row['unit_available']); ?></p>
                    <form method="POST" action="" class="add-to-cart-form">
                        <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                        <input type="number" name="unit_ordered" min="1" max="<?php echo htmlspecialchars($row['unit_available']); ?>" required>
                        <button type="submit" name="add_to_cart" class="orange-button">Add to Cart</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- Cart Section for Customer -->
    <?php if ($user_type == 'customer' && isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
        <h2>Your Cart</h2>
        <table>
            <thead>
                <tr>
                    <th>Food Name</th>
                    <th>Units Ordered</th>
                    <th>Price (BDT)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_cost = 0; // Initialize total cost
                foreach ($_SESSION['cart'] as $cart_item): ?>
                    <?php 
                        // Fetch price of the food
                        $food_name = $cart_item['food_name'];
                        $food_sql = "SELECT unit_price FROM food WHERE name = '$food_name'";
                        $food_result = $conn->query($food_sql);
                        $food = $food_result->fetch_assoc();
                        $total_price = $cart_item['unit_ordered'] * $food['unit_price'];
                        $total_cost += $total_price; // Add to total cost
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cart_item['food_name']); ?></td>
                        <td><?php echo htmlspecialchars($cart_item['unit_ordered']); ?></td>
                        <td><?php echo htmlspecialchars($total_price); ?> BDT</td>
                        <td>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($cart_item['food_name']); ?>">
                                <button type="submit" name="remove_from_cart" class="remove-button">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><strong>Total Cost: </strong><?php echo $total_cost; ?> BDT</p> <!-- Display total cost -->
        <form method="POST" action="">
            <button type="submit" name="place_order" class="orange-button">Place Order</button>
        </form>
    <?php endif; ?>

    <!-- Order Confirmation Section for Admin and Teller -->
    <?php if (($user_type == 'admin' || $user_type == 'teller') && $orders_result): ?>
        <div class="admin-section">
            <h2>Orders</h2>

            <!-- Admin: Filter Orders by Date -->
            <?php if ($user_type == 'admin'): ?>
                <form method="POST" action="">
                    <input type="date" name="order_date" required>
                    <button type="submit" name="filter_orders_by_date" class="orange-button">Filter by Date</button>
                </form>
            <?php endif; ?>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer ID</th>
                        <th>Food Name</th>
                        <th>Units Ordered</th>
                        <th>Price (BDT)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['food_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['unit_ordered']); ?></td>
                            <td><?php echo htmlspecialchars($order['unit_price'] * $order['unit_ordered']); ?> BDT</td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td>
                                <?php if ($order['status'] == 'pending'): ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <button type="submit" name="confirm_order" class="orange-button">Confirm</button>
                                    </form>
                                <?php else: ?>
                                    <button class="orange-button" disabled>Served</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Logout -->
<form method="POST" action="">
    <button type="submit" name="logout" class="logout-btn">Logout</button>
</form>

</body>
</html>
