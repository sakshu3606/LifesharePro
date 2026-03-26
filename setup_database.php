<?php
/**
 * Database Setup Script
 * Run this file once to create the database and tables
 * Access via: http://localhost/lifeshare/setup_database.php
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');   // ✅ Your real password
define('DB_NAME', 'lifeshare_db');

$errors = [];
$success = [];

try {
    // Connect to MySQL server (without database)
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $success[] = "✓ Connected to MySQL server";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if ($conn->query($sql) === TRUE) {
        $success[] = "✓ Database '" . DB_NAME . "' created or already exists";
    } else {
        $errors[] = "✗ Error creating database: " . $conn->error;
    }
    
    // Select database
    $conn->select_db(DB_NAME);
    $success[] = "✓ Database selected";
    
    // Create blood_donors table
    $sql = "CREATE TABLE IF NOT EXISTS blood_donors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        dob DATE NOT NULL,
        gender ENUM('male', 'female', 'other') NOT NULL,
        blood_group VARCHAR(5) NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(50) NOT NULL,
        state VARCHAR(50) NOT NULL,
        last_donation DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_blood_group (blood_group),
        INDEX idx_city (city)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        $success[] = "✓ Table 'blood_donors' created successfully";
    } else {
        $errors[] = "✗ Error creating blood_donors table: " . $conn->error;
    }
    
    // Create organ_donors table
    $sql = "CREATE TABLE IF NOT EXISTS organ_donors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        dob DATE NOT NULL,
        gender ENUM('male', 'female', 'other') NOT NULL,
        blood_group VARCHAR(5) NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(50) NOT NULL,
        state VARCHAR(50) NOT NULL,
        organs TEXT NOT NULL,
        emergency_contact VARCHAR(100) NOT NULL,
        emergency_phone VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_city (city)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        $success[] = "✓ Table 'organ_donors' created successfully";
    } else {
        $errors[] = "✗ Error creating organ_donors table: " . $conn->error;
    }
    
    // Insert sample data (optional)
    $sql = "SELECT COUNT(*) as count FROM blood_donors";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $sampleData = [
            ["John Doe", "john@example.com", "1234567890", "1990-05-15", "male", "O+", "123 Main St", "Mumbai", "Maharashtra", NULL]
        ];
        
        $stmt = $conn->prepare("INSERT INTO blood_donors (full_name, email, phone, dob, gender, blood_group, address, city, state, last_donation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sampleData as $data) {
            $stmt->bind_param("ssssssssss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9]);
            $stmt->execute();
        }
        
        $success[] = "✓ Sample blood donor data inserted";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $errors[] = "✗ Exception: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - LifeShare</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .status {
            margin: 15px 0;
            padding: 15px 20px;
            border-radius: 10px;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .success {
            background: #d4edda;
            border-left: 5px solid #28a745;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border-left: 5px solid #dc3545;
            color: #721c24;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        
        .summary h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .icon {
            font-size: 3rem;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"><?php echo empty($errors) ? '✅' : '⚠️'; ?></div>
        <h1>Database Setup</h1>
        <p class="subtitle">LifeShare Database Initialization</p>
        
        <?php foreach ($success as $msg): ?>
            <div class="status success"><?php echo $msg; ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($errors as $msg): ?>
            <div class="status error"><?php echo $msg; ?></div>
        <?php endforeach; ?>
        
        <?php if (empty($errors)): ?>
            <div class="summary">
                <h3>🎉 Setup Completed Successfully!</h3>
                <p>Your LifeShare database is ready to use. You can now:</p>
                <ul style="margin: 15px 0 0 20px; line-height: 2;">
                    <li>Register blood donors</li>
                    <li>Accept organ donation pledges</li>
                    <li>Search for donors</li>
                    <li>View statistics</li>
                </ul>
            </div>
            <a href="index.php" class="btn">Go to Home Page →</a>
        <?php else: ?>
            <div class="summary">
                <h3>⚠️ Setup Incomplete</h3>
                <p>There were some errors during setup. Please check the error messages above and try again.</p>
            </div>
            <a href="setup_database.php" class="btn">Try Again</a>
        <?php endif; ?>
    </div>
</body>
</html>