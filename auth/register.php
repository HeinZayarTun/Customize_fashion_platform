<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_POST) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $role = sanitizeInput($_POST['role']);
    
    // Password validation
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $error = "Password must contain at least one special character.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Artists need approval, customers are auto-approved
        $is_approved = ($role === 'artist') ? 0 : 1;
        
        $query = "INSERT INTO users (name, email, password, role, is_approved) VALUES (:name, :email, :password, :role, :is_approved)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':is_approved', $is_approved);
        
        if ($stmt->execute()) {
            if ($role === 'artist') {
                $success = "Registration successful! Your account is pending admin approval.";
            } else {
                header('Location: login.php');
                exit();
            }
        } else {
            $error = "Registration failed. Email may already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Fashion Platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', system-ui, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .register-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 450px;
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    .register-container h2 {
        text-align: center;
        color: #2c3e50;
        font-size: 2rem;
        margin-bottom: 30px;
        position: relative;
    }
    
    .register-container h2::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 2px;
    }
    
    .success {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
        box-shadow: 0 8px 25px rgba(39,174,96,0.3);
    }
    
    .error {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
        box-shadow: 0 8px 25px rgba(231,76,60,0.3);
    }
    
    form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    input, select {
        padding: 15px 20px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: rgba(255,255,255,0.8);
    }
    
    input:focus, select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        background: white;
    }
    
    .password-requirements {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        border-left: 4px solid #667eea;
        font-size: 14px;
        color: #6c757d;
    }
    
    .password-requirements ul {
        margin: 8px 0 0 20px;
    }
    
    .password-requirements li {
        margin-bottom: 5px;
    }
    
    button {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 15px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 8px 20px rgba(102,126,234,0.3);
    }
    
    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(102,126,234,0.4);
    }
    
    p {
        text-align: center;
        margin-top: 25px;
        color: #6c757d;
    }
    
    p a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    
    p a:hover {
        color: #764ba2;
    }
    
    @media (max-width: 480px) {
        .register-container {
            padding: 30px 20px;
        }
        
        .register-container h2 {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Join Us</h2>
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            
            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li>At least 6 characters long</li>
                    <li>Contains at least one number</li>
                    <li>Contains at least one special character</li>
                </ul>
            </div>
            
            <select name="role" required>
                <option value="">Select Your Role</option>
                <option value="customer">Customer</option>
                <option value="artist">Artist</option>
            </select>
            <button type="submit">Create Account</button>
        </form>
        
        <p><a href="login.php">Already have an account? Sign in</a></p>
    </div>
</body>
</html>