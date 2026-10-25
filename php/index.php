<?php
session_start();

// Простая авторизация (в реальном проекте хэшируйте пароль)
$admin_login = 'admin';
$admin_pass = 'admin123';

if (isset($_POST['login']) && isset($_POST['password'])) {
    if ($_POST['login'] === $admin_login && $_POST['password'] === $admin_pass) {
        $_SESSION['admin_auth'] = true;
    } else {
        $error = "Неверный логин или пароль!";
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_auth']);
    header("Location: index.php");
    exit();
}

// Форма входа, если не авторизован
if (!isset($_SESSION['admin_auth'])) {
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card p-4 shadow-sm" style="width: 350px;">
        <h3 class="card-title text-center mb-3">Авторизация</h3>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger p-2 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Логин</label>
                <input type="text" name="login" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Пароль</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Войти</button>
        </form>
    </div>
</body>
</html>
<?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель AM2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Боковое меню -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar vh-100 text-white p-3">
            <h4 class="text-center mb-4">AM2 Admin</h4>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="?page=dashboard" class="nav-link text-white active">
                        <i class="bi bi-speedometer2 me-2"></i> Дашборд
                    </a>
                </li>
                <li>
                    <a href="?page=orders" class="nav-link text-white">
                        <i class="bi bi-cart me-2"></i> Заказы и Оплаты
                    </a>
                </li>
                <li>
                    <a href="?page=settings" class="nav-link text-white">
                        <i class="bi bi-gear me-2"></i> Настройки Enot
                    </a>
                </li>
            </ul>
            <hr>
            <a href="?logout=1" class="btn btn-danger w-100">Выйти</a>
        </nav>

        <!-- Основной контент -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <?php
            $page = $_GET['page'] ?? 'dashboard';

            switch ($page) {
                case 'orders':
                    echo '<h2>Список заказов и платежей</h2>';
                    echo '<p>Здесь отображаются логи платежной системы Enot.</p>';
                    // Таблица заказов
                    break;
                case 'settings':
                    echo '<h2>Настройки интеграции Enot</h2>';
                    ?>
                    <form method="POST" action="" class="mt-3" style="max-width: 500px;">
                        <div class="mb-3">
                            <label class="form-label">ID магазина (Merchant ID)</label>
                            <input type="text" class="form-control" name="merchant_id" placeholder="xxxx-xxxx-xxxx">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Секретный пароль</label>
                            <input type="password" class="form-control" name="secret_key">
                        </div>
                        <button type="submit" class="btn btn-success">Сохранить</button>
                    </form>
                    <?php
                    break;
                default:
                    ?>
                    <h2>Панель управления</h2>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white p-3">
                                <h5>Всего заказов</h5>
                                <h3>124</h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white p-3">
                                <h5>Доход (Enot)</h5>
                                <h3>45,200 ₽</h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark p-3">
                                <h5>Успешных транзакций</h5>
                                <h3>98%</h3>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;
            }
            ?>
        </main>
    </div>
</div>

</body>
</html>
