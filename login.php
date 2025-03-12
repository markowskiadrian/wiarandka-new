<?php
session_start();
include 'config.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_banned']) {
            echo "Your account has been banned!";
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            header("Location: main.php");
            exit();
        }
    } else {
        echo "Invalid credentials!";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wiarandka - login</title>

    <link rel="stylesheet" href="css/default.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <section>
        <h2>Zaloguj się <br>do <strong>Wiarandki</strong></h2>
        <form method="POST">
            <p>Login</p>
            <input type="text" name="username" required class="rounded">
            <p>Hasło</p>
            <input type="password" name="password" required class="rounded">
            <a href="register.php">
                <p>Nie pamiętam hasła</p>
            </a>
            <button type="submit" name="login" class="rounded"><span><i class="bi bi-check-all"></i>
                    Login</span></button>
            <a href="register.php" id="register">
                <p>Albo załóż konto</p>
            </a>
        </form>

        <div>
            <p>Lub zaloguj się przez:</p>
            <div>
                <i class="bi bi-apple"></i>
                <i class="bi bi-google"></i>
                <i class="bi bi-facebook"></i>
            </div>
        </div>

    </section>
</body>

</html>