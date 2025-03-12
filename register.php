<?php include 'config.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Wiarandka - Registration</title>
</head>
<body>
    <h2>Registration</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="email" name="email" placeholder="Email" required>
        <select name="gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
        <select name="religion" required>
            <option value="Christianity">Christianity</option>
            <option value="Islam">Islam</option>
            <option value="Judaism">Judaism</option>
        </select>
        <input type="text" name="denomination" placeholder="Denomination">
        <textarea name="bio" placeholder="About you"></textarea>
        <button type="submit" name="register">Register</button>
    </form>

    <?php
    if(isset($_POST['register'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
        $religion = $_POST['religion'];
        $denomination = $_POST['denomination'];
        $bio = $_POST['bio'];
        $gender = $_POST['gender']; 

        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, gender, religion, denomination, bio) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $gender, $religion, $denomination, $bio]);
        
        echo "Registration successful! <a href='login.php'>Login here</a>";
    }
    ?>
</body>
</html>