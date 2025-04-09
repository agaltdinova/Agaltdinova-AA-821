<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = trim($_POST['project_name'] ?? '');

    if ($projectName === '') {
        $errors[] = 'Название проекта не может быть пустым.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO projects (project_name, owner_id) VALUES (:project_name, :owner_id)');
            $stmt->execute([
                ':project_name' => $projectName,
                ':owner_id' => $user_id
            ]);

            $_SESSION['success'] = 'Проект успешно создан!';

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Ошибка при создании проекта: ' . htmlspecialchars($e->getMessage());
        }
    }
}

try {
    $stmt = $pdo->prepare('SELECT id, project_name FROM projects WHERE owner_id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $ownProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('
        SELECT DISTINCT p.id, p.project_name
        FROM projects p
        JOIN tasks t ON p.id = t.project_id
        WHERE t.assigned_to = :user_id
    ');
    $stmt->execute([':user_id' => $user_id]);
    $assignedProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $projects = [];
    $projectIds = [];

    foreach (array_merge($ownProjects, $assignedProjects) as $project) {
        if (!in_array($project['id'], $projectIds)) {
            $projects[] = $project;
            $projectIds[] = $project['id'];
        }
    }
} catch (PDOException $e) {
    die('Ошибка базы данных: ' . htmlspecialchars($e->getMessage()));
}
?>

<?php
if (isset($_SESSION['success'])) {
    echo '<p class="success-message">' . $_SESSION['success'] . '</p>';
    unset($_SESSION['success']);
}

if (!empty($errors)) {
    foreach ($errors as $error) {
        echo '<p class="error-message">' . $error . '</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/projects.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1>TTRCKR</h1>
            </div>
            <div class="header-buttons">
                <a href="dashboard.php" class="btn btn-secondary">Мои проекты</a>
                <a href="logout.php" class="btn btn-logout">Выход</a>
            </div>
        </div>
    </header>
    <main>
        <div class="container">
            <div class="header-wrapper">
                <h2>Мои проекты</h2>
                <button id="toggle-form" class="btn btn-secondary">Создать новый проект</button>
            </div>

            <form method="post" class="new-project-form" id="new-project-form" style="display: none;">
                <input type="text" name="project_name" placeholder="Название проекта" required>
                <button type="submit" class="btn btn-primary">Создать</button>
            </form>

            <?php if (isset($_SESSION['success'])): ?>
                <p class="success"><?= htmlspecialchars($_SESSION['success']) ?></p>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <ul class="error">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (count($projects) > 0): ?>
                <div class="projects-container" id="projectContainer">
                    <?php foreach ($projects as $project): ?>
                        <div class="project-card">
                            <h3><a href="project_tasks.php?project_id=<?= htmlspecialchars($project['id']) ?>"><?= htmlspecialchars($project['project_name']) ?></a></h3>
                            <a href="project_tasks.php?project_id=<?= htmlspecialchars($project['id']) ?>" class="btn btn-secondary">Посмотреть задачи</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Вы пока не участвуете ни в одном проекте.</p>
            <?php endif; ?>

        </div>
    </main>

    <script>
        const toggleButton = document.getElementById('toggle-form');
        const form = document.getElementById('new-project-form');

        toggleButton.addEventListener('click', function() {
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        });

        const container = document.getElementById('projectContainer');
        const projects = Array.from(container.children);
        projects.reverse().forEach(p => container.appendChild(p));
    </script>

</body>

</html>