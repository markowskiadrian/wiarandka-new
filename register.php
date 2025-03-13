<?php include 'config.php'; ?>
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
        <p class="close"><a href="login.php"><i class="bi bi-x"></i></a></p>
        <h2>Dołącz do<br><strong>Wiarandki</strong></h2>
        <form method="POST">
            <p>Nazwa użytkownika</p>
            <input type="text" name="username" required class="rounded">
            <p>Hasło</p>
            <input type="password" name="password" required class="rounded">
            <p>E-mail</p>
            <input type="email" name="email" required class="rounded">
            <p>Płeć i religia</p>
            <select name="gender" required class="rounded">
                <option value="Male">Mężczyzna</option>
                <option value="Female">Kobieta</option>
            </select>
            <select name="religion" required class="rounded" id="religion">
                <option value="Christianity">Chrześcijaństwo</option>
                <option value="Islam">Islam</option>
                <option value="Judaism">Judaizm</option>
            </select>
            <hr>
            <p class="dark">Odłam religii</p>
            <input type="text" name="denomination" class="rounded2">
            <p class="dark">Opis profilu</p>
            <textarea name="bio" class="rounded2"></textarea>
            <button type="submit" name="register" class="rounded" id="register"><span><i class="bi bi-check-all"></i>
            Zarejestruj się</span></button>
        </form>
    </section>


    <?php
    if (isset($_POST['register'])) {
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

        Header("Location: login.php");
    }
    ?>
</body>

</html>