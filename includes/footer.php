    <footer class="footer">
        <div class="footer-content">
            <div class="footer-info">
                <p>&copy; <?php echo date('Y'); ?> Fashion Platform. All rights reserved.</p>
            </div>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </footer>
    
    <style>
    .footer {
        background: #2c3e50;
        color: white;
        padding: 2rem 0;
        margin-top: auto;
    }
    
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .footer-info p {
        margin: 0;
        font-size: 0.9rem;
    }
    
    .footer-links {
        display: flex;
        gap: 2rem;
    }
    
    .footer-links a {
        color: white;
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s;
    }
    
    .footer-links a:hover {
        color: #3498db;
    }
    
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            text-align: center;
            gap: 1.5rem;
        }
        
        .footer-links {
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }
        
        .footer-links a {
            font-size: 0.8rem;
        }
    }
    
    @media (max-width: 480px) {
        .footer {
            padding: 1.5rem 0;
        }
        
        .footer-content {
            padding: 0 1rem;
        }
        
        .footer-info p {
            font-size: 0.8rem;
        }
    }
    </style>
    
    <script src="<?php echo isset($js_path) ? $js_path : '../assets/js/main.js'; ?>"></script>
</body>
</html>