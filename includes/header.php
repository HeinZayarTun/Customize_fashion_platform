<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Fashion Platform'; ?></title>
    <link rel="stylesheet" href="<?php echo isset($css_path) ? $css_path : '../assets/css/style.css'; ?>">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar">
        <div class="nav-brand">
            <span class="brand-icon">✨</span>
            Fashion Platform
        </div>
        <div class="nav-toggle" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-menu">
            <?php if ($_SESSION['user_role'] == 'customer'): ?>
                <li><a href="../customer/dashboard.php"><span>🏠</span> Home</a></li>
                <li><a href="../customer/customize.php"><span>🎨</span> Customize</a></li>
                <li><a href="../customer/orders.php"><span>📦</span> My Orders</a></li>
                <li><a href="../includes/profile.php"><span>👤</span> Profile</a></li>
            <?php elseif ($_SESSION['user_role'] == 'artist'): ?>
                <li><a href="../artist/dashboard.php"><span>📊</span> Dashboard</a></li>
                <li><a href="../artist/orders.php"><span>🎯</span> Orders</a></li>
                <li><a href="../artist/earnings.php"><span>💰</span> Earnings</a></li>
                <li><a href="../includes/profile.php"><span>👤</span> Profile</a></li>
            <?php elseif ($_SESSION['user_role'] == 'admin'): ?>
                <li><a href="../admin/dashboard.php"><span>⚡</span> Dashboard</a></li>
                <li><a href="../admin/users.php"><span>👥</span> Users</a></li>
                <li><a href="../admin/orders.php"><span>📋</span> Orders</a></li>
                <li><a href="../admin/products.php"><span>🛍️</span> Products</a></li>
                <li><a href="../admin/settings.php"><span>⚙️</span> Settings</a></li>
                <li><a href="../includes/profile.php"><span>👤</span> Profile</a></li>
            <?php endif; ?>
            <li><a href="../auth/logout.php" class="logout-btn"><span>🚪</span> Logout</a></li>
        </ul>
    </nav>
    
    <style>
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.2rem 2.5rem;
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        position: relative;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        backdrop-filter: blur(10px);
    }
    
    .nav-brand {
        font-size: 1.6rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .brand-icon {
        font-size: 1.8rem;
        -webkit-text-fill-color: #667eea;
    }
    
    .nav-toggle {
        display: none;
        flex-direction: column;
        cursor: pointer;
        padding: 0.8rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .nav-toggle:hover {
        background: rgba(255,255,255,0.1);
    }
    
    .nav-toggle span {
        width: 28px;
        height: 3px;
        background: white;
        margin: 4px 0;
        transition: 0.3s;
        border-radius: 2px;
    }
    
    .nav-menu {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 1rem;
    }
    
    .nav-menu a {
        color: rgba(255,255,255,0.9);
        text-decoration: none;
        padding: 0.8rem 1.2rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        position: relative;
        overflow: hidden;
    }
    
    .nav-menu a::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(102,126,234,0.2), rgba(118,75,162,0.2));
        transition: left 0.3s ease;
        z-index: -1;
    }
    
    .nav-menu a:hover::before {
        left: 0;
    }
    
    .nav-menu a:hover {
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .nav-menu a span {
        font-size: 1.1rem;
    }
    
    .logout-btn {
        background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
        color: white !important;
        border-radius: 25px !important;
    }
    
    .logout-btn:hover {
        background: linear-gradient(135deg, #c0392b, #a93226) !important;
        transform: translateY(-2px) !important;
    }
    
    @media (max-width: 768px) {
        .navbar {
            padding: 1rem 1.5rem;
        }
        
        .nav-toggle {
            display: flex;
        }
        
        .nav-menu {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            flex-direction: column;
            gap: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .nav-menu.active {
            max-height: 500px;
        }
        
        .nav-menu li {
            width: 100%;
        }
        
        .nav-menu a {
            display: flex;
            padding: 1.2rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            border-radius: 0;
        }
        
        .nav-menu a:hover {
            background: rgba(102,126,234,0.2);
            transform: none;
        }
        
        .nav-brand {
            font-size: 1.4rem;
        }
    }
    
    @media (max-width: 480px) {
        .navbar {
            padding: 0.8rem 1rem;
        }
        
        .nav-brand {
            font-size: 1.2rem;
        }
        
        .brand-icon {
            font-size: 1.4rem;
        }
    }
    </style>
    
    <script>
    function toggleMenu() {
        const menu = document.querySelector('.nav-menu');
        const toggle = document.querySelector('.nav-toggle');
        menu.classList.toggle('active');
        
        // Animate hamburger
        const spans = toggle.querySelectorAll('span');
        if (menu.classList.contains('active')) {
            spans[0].style.transform = 'rotate(45deg) translate(9px, 9px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
        } else {
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    }
    </script>
    <?php endif; ?>
