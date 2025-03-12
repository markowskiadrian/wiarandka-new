<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle profile picture upload
if(isset($_POST['update_picture'])) {
    $target_dir = "profilepics/";
    $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is actual image
    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);  
    if($check !== false) {
        // Allow certain file formats
        if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg") {
            if(move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
                $stmt->execute([$target_file, $_SESSION['user_id']]);
                echo "Profile picture updated!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings</title>
</head>
<body>
    <h2>Profile Settings</h2>
    
    <form method="POST" enctype="multipart/form-data">
        <h3>Update Profile Picture</h3>
        <input type="file" name="profile_pic" accept="image/*" required>
        <button type="submit" name="update_picture">Update Picture</button>
    </form>
    
    <p><a href="main.php">Back to Main</a></p>
</body>
</html>