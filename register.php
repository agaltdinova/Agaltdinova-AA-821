<?php
require_once 'db.php';

$errors = [];

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $errors[] = "Пароли не совпадают.";
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            $errors[] = "Пользователь с таким email уже существует!";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $hashed_password
            ]);

            header('Location: login.php');
            exit();
        }
    }
} catch (PDOException $e) {
    $errors[] = 'Ошибка подключения: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
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
            <h1>Регистрация</h1>
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" id="registrationForm">
                <!-- Поле для полного имени -->
                <div class="form-group">
                    <label for="name">ФИО</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        placeholder="Введите ФИО"
                        pattern="[А-Яа-яЁё\s]+"
                        title="Только символы кириллицы и пробелы"
                        required>
                    <span class="error" id="nameError"></span>
                </div>


                <!-- Поле для email -->
                <div class="form-group">
                    <label for="email">Электронная почта</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Введите электронную почту"
                        required>
                    <span class="error" id="emailError"></span>
                </div>


                <!-- Поле для пароля minlength="6" -->
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Введите пароль"

                        required>
                    <span class="error" id="passwordError"></span>
                </div>

                <!-- Поле для подтверждения пароля -->
                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Повторите пароль"
                        required>
                    <span class="error" id="confirmPasswordError"></span>
                </div>

                <!-- Кнопка отправки -->
                <div class="form-group">
                    <button type="submit" class="btn">Зарегистрироваться</button>
                </div>
            </form>

            <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
        </div>
    </main>

</body>

</html>