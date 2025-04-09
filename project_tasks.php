<?php
session_start();
require_once 'db.php';
$projectId = $_GET['project_id'] ?? null;
if (!$projectId) {
    header('Location: index.php');
    exit;
}
$stmt = $pdo->prepare('
    SELECT p.id, p.project_name, p.owner_id, u.name AS owner_name 
    FROM projects p 
    JOIN users u ON p.owner_id = u.id 
    WHERE p.id = :project_id
');
$stmt->execute([':project_id' => $projectId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    echo "Проект не найден!";
    exit;
}
$isOwner = $_SESSION['user_id'] === $project['owner_id'];
$filterStatus = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$query = '
    SELECT t.id, t.title, t.description, t.status, t.priority, t.start_date, t.end_date, u.name AS assigned_to_name 
    FROM tasks t 
    JOIN users u ON t.assigned_to = u.id 
    WHERE t.project_id = :project_id
';
if ($filterStatus) {
    $query .= ' AND t.status = :status';
}
if ($filterPriority) {
    $query .= ' AND t.priority = :priority';
}
$stmt = $pdo->prepare($query);
$params = [':project_id' => $projectId];
if ($filterStatus) $params[':status'] = $filterStatus;
if ($filterPriority) $params[':priority'] = $filterPriority;
$priorityTexts = [
    'low' => 'Низкий',
    'medium' => 'Средний',
    'high' => 'Высокий'
];
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['delete_task'])) {
    $taskId = $_GET['delete_task'];
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
    $stmt->execute([':id' => $taskId]);
    header('Location: project_tasks.php?project_id=' . $projectId);
    exit;
}
if (isset($_POST['update_status'])) {
    $taskId = $_POST['task_id'];
    $newStatus = $_POST['status'];
    $stmt = $pdo->prepare('UPDATE tasks SET status = :status WHERE id = :id');
    $stmt->execute([':status' => $newStatus, ':id' => $taskId]);
    header('Location: project_tasks.php?project_id=' . $projectId);
    exit;
}
if (isset($_POST['create_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $priority = $_POST['priority'];
    $assignedTo = $_POST['assigned_to'];

    if ($endDate < $startDate) {
        echo "<p style='color: red;'>Ошибка: дата окончания не может быть раньше даты начала.</p>";
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO tasks (title, description, start_date, end_date, priority, assigned_to, project_id) 
            VALUES (:title, :description, :start_date, :end_date, :priority, :assigned_to, :project_id)
        ');
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':priority' => $priority,
            ':assigned_to' => $assignedTo,
            ':project_id' => $projectId
        ]);
        header('Location: project_tasks.php?project_id=' . $projectId);
        exit;
    }}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Задачи проекта "<?php echo htmlspecialchars($project['project_name']); ?>"</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tasks.css">


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
            <h1>Задачи проекта "<?php echo htmlspecialchars($project['project_name']); ?>"</h1>

            <?php if ($isOwner): ?>
                <button class="btn btn-primary" id="toggleTaskForm">+ Новая задача</button>

                <form method="POST" id="taskForm" style="display: none; margin-top: 15px;">
                    <h3>Создать задачу</h3>
                    <input type="text" name="title" placeholder="Заголовок задачи" required>
                    <textarea name="description" placeholder="Описание задачи" required></textarea>
                    <input type="date" name="start_date" required>
                    <input type="date" name="end_date" required>
                    <select name="priority" required>
                        <option value="low">Низкий</option>
                        <option value="medium">Средний</option>
                        <option value="high">Высокий</option>
                    </select>
                    <select name="assigned_to" required>
                        <option value="">Назначить ответственному</option>
                        <?php
                        $usersStmt = $pdo->query('SELECT id, name FROM users');
                        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($users as $user):
                        ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="create_task" class="btn btn-success">Создать</button>
                </form>
            <?php endif; ?>


            <!-- Фильтрация задач -->
            <form method="GET">
                <input type="hidden" name="project_id" value="<?= $projectId ?>">
                <select name="status">
                    <option value="">Все статусы</option>
                    <option value="todo" <?= $filterStatus === 'todo' ? 'selected' : '' ?>>Не начата</option>
                    <option value="inprogress" <?= $filterStatus === 'inprogress' ? 'selected' : '' ?>>В процессе</option>
                    <option value="done" <?= $filterStatus === 'done' ? 'selected' : '' ?>>Завершена</option>
                </select>
                <select name="priority">
                    <option value="">Все приоритеты</option>
                    <option value="low" <?= $filterPriority === 'low' ? 'selected' : '' ?>>Низкий</option>
                    <option value="medium" <?= $filterPriority === 'medium' ? 'selected' : '' ?>>Средний</option>
                    <option value="high" <?= $filterPriority === 'high' ? 'selected' : '' ?>>Высокий</option>
                </select>
                <button type="submit" class="btn btn-secondary">Фильтровать</button>
            </form>

            <!-- Список задач -->
            <div class="tasks-container">
                <?php if (empty($tasks)): ?>
                    <p>Нет задач в этом проекте.</p>
                <?php else: ?>
                    <table class="tasks-table">
                        <thead>
                            <tr>
                                <th>Заголовок</th>
                                <th>Описание</th>
                                <th>Статус</th>
                                <th>Дата начала</th>
                                <th>Дата окончания</th>
                                <th>Приоритет</th>
                                <th>Ответственный</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?= htmlspecialchars($task['title']) ?></td>
                                    <td><?= htmlspecialchars($task['description']) ?></td>
                                    <td>
                                        <form method="POST">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="todo" <?= $task['status'] === 'todo' ? 'selected' : '' ?>>Не начата</option>
                                                <option value="inprogress" <?= $task['status'] === 'inprogress' ? 'selected' : '' ?>>В процессе</option>
                                                <option value="done" <?= $task['status'] === 'done' ? 'selected' : '' ?>>Завершена</option>
                                            </select>
                                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td><?= htmlspecialchars($task['start_date']) ?></td>
                                    <td><?= htmlspecialchars($task['end_date']) ?></td>
                                    <td> <?= isset($priorityTexts[$task['priority']]) ? htmlspecialchars($priorityTexts[$task['priority']]) : 'Не указан' ?></td>
                                    <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                                    <td>
                                        <a style="text-decoration: none;" href="project_tasks.php?project_id=<?= $projectId ?>&delete_task=<?= $task['id'] ?>" class="btn btn-danger">Удалить</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>
        document.getElementById('toggleTaskForm').addEventListener('click', function() {
            const form = document.getElementById('taskForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });

        document.getElementById('taskForm').addEventListener('submit', function(e) {
        const start = new Date(this.start_date.value);
        const end = new Date(this.end_date.value);

        if (end < start) {
            e.preventDefault();
            alert("Дата окончания не может быть раньше даты начала!");
        }
    });
    </script>

</body>

</html>