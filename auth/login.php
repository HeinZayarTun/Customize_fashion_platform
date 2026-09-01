<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_POST) {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    $query = "SELECT id, email, password, role, name, is_approved FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            // Check if artist is approved
            if ($user['role'] === 'artist' && !$user['is_approved']) {
                $error = "Your account is pending admin approval.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                
                switch ($user['role']) {
                    case 'admin':
                        header('Location: ../admin/dashboard.php');
                        break;
                    case 'artist':
                        header('Location: ../artist/dashboard.php');
                        break;
                    case 'customer':
                        header('Location: ../customer/dashboard.php');
                        break;
                }
                exit();
            }
        } else {
            $error = "Invalid credentials";
        }
    } else {
        $error = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Fashion Platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', system-ui, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
        overflow: hidden;
    }
    
    body::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 50px 50px;
        animation: float 20s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        50% { transform: translate(-20px, -20px) rotate(180deg); }
    }
    
    .login-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(25px);
        padding: 50px 40px;
        border-radius: 25px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.15), 0 0 0 1px rgba(255,255,255,0.2);
        width: 100%;
        max-width: 420px;
        position: relative;
        z-index: 1;
    }
    
    .login-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
        border-radius: 25px 25px 0 0;
    }
    
    .brand-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .brand-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        display: block;
    }
    
    .login-container h2 {
        text-align: center;
        color: #2c3e50;
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #2c3e50, #34495e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .subtitle {
        text-align: center;
        color: #7f8c8d;
        font-size: 1rem;
        margin-bottom: 35px;
        font-weight: 400;
    }
    
    .error {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        padding: 18px 20px;
        border-radius: 15px;
        margin-bottom: 25px;
        text-align: center;
        font-weight: 500;
        box-shadow: 0 10px 25px rgba(231,76,60,0.3);
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    form {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    
    .input-group {
        position: relative;
    }
    
    .input-group input {
        width: 100%;
        padding: 18px 20px 18px 50px;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: rgba(255,255,255,0.9);
        font-weight: 400;
    }
    
    .input-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102,126,234,0.1);
        background: white;
        transform: translateY(-2px);
    }
    
    .input-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.2rem;
        color: #95a5a6;
        transition: color 0.3s ease;
    }
    
    .input-group input:focus + .input-icon {
        color: #667eea;
    }
    
    button {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 18px 20px;
        border: none;
        border-radius: 15px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 10px 25px rgba(102,126,234,0.3);
        position: relative;
        overflow: hidden;
    }
    
    button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    button:hover::before {
        left: 100%;
    }
    
    button:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(102,126,234,0.4);
    }
    
    button:active {
        transform: translateY(-1px);
    }
    
    .footer-link {
        text-align: center;
        margin-top: 30px;
        padding-top: 25px;
        border-top: 1px solid #ecf0f1;
    }
    
    .footer-link p {
        color: #7f8c8d;
        font-size: 15px;
        margin-bottom: 10px;
    }
    
    .footer-link a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
        display: inline-block;
    }
    
    .footer-link a:hover {
        color: #764ba2;
        transform: translateY(-1px);
    }
    
    @media (max-width: 480px) {
        .login-container {
            padding: 40px 25px;
            margin: 10px;
        }
        
        .login-container h2 {
            font-size: 1.8rem;
        }
        
        .brand-icon {
            font-size: 2.5rem;
        }
        
        .input-group input {
            padding: 16px 18px 16px 45px;
        }
        
        button {
            padding: 16px 18px;
        }
    }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand-header">
            <span class="brand-icon">✨</span>
            <h2>Welcome Back</h2>
            <p class="subtitle">Sign in to your Fashion Platform account</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required>
                <span class="input-icon">📧</span>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
                <span class="input-icon">🔒</span>
            </div>
            
            <button type="submit">
                <span>🚀</span> Sign In
            </button>
        </form>
        
        <div class="footer-link">
            <p>New to Fashion Platform?</p>
            <a href="register.php">🎆 Create Your Account</a>
        </div>
    </div>
</body>
</html>