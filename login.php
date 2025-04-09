<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $formPassword = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($formPassword, $user['password_hash'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Неверный логин или пароль!";
            }
        } else {
            $error = "Пользователь не найден!";
        }
    } catch (PDOException $e) {
        die('Ошибка базы данных: ' . htmlspecialchars($e->getMessage()));
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Авторизация</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>

<body>
    <!-- Хэдер -->
    <header>
        <div class="container">
            <div class="logo">
                <h1>TTRCKR</h1>
            </div>
        </div>
    </header>

    <main>
        <div class="form-container">
            <h1>Авторизация</h1>
            <p>Используйте электронную почту и пароль:</p>
            <form action="login.php" method="POST">
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Электронная почта</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Войти</button>
                </div>
            </form>

            <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
        </div>
    </main>

</body>

</html>