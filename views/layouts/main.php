<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'AutoRewrite'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5 class="text-white">AutoRewrite</h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="/">
                                <i class="fas fa-home me-2"></i>
                                Главная
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" href="/settings">
                                <i class="fas fa-cog me-2"></i>
                                Настройки
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?php echo $currentPage === 'image-settings' ? 'active' : ''; ?>" href="/image-settings">
                                <i class="fas fa-image me-2"></i>
                                Генерация изображений
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?php echo $currentPage === 'proxies' ? 'active' : ''; ?>" href="/proxies">
                                <i class="fas fa-shield-alt me-2"></i>
                                Прокси
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?php echo $currentPage === 'accounts' ? 'active' : ''; ?>" href="/accounts">
                                <i class="fas fa-users me-2"></i>
                                Аккаунты
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?php echo $currentPage === 'parsing' ? 'active' : ''; ?>" href="/parsing">
                                <i class="fas fa-spider me-2"></i>
                                Настройки парсинга
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?php echo $currentPage === 'rewrite' ? 'active' : ''; ?>" href="/rewrite">
                                <i class="fas fa-pen-fancy me-2"></i>
                                Реврайт
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo isset($pageTitle) ? $pageTitle : 'Панель управления'; ?></h1>
                </div>
                
                <!-- Page content -->
                <?php echo $content; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/main.js"></script>
</body>
</html>
