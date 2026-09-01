<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function redirectTo($location) {
    header("Location: $location");
    exit();
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function uploadFile($file, $targetDir) {
    $targetFile = $targetDir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    if(isset($_POST["submit"])) {
        $check = getimagesize($file["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            $uploadOk = 0;
        }
    }
    
    if ($uploadOk == 0) {
        return false;
    } else {
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            return $targetFile;
        } else {
            return false;
        }
    }
}

function generateOrderId() {
    return 'ORD' . date('Ymd') . rand(1000, 9999);
}
?>