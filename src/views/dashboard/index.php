<div class="row">
    <!-- Статистика -->
    <div class="col-md-12 mb-4">
        <div class="row">
            <!-- Аккаунты -->
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users dashboard-icon"></i>
                        <h5 class="card-title">Аккаунты</h5>
                        <h2><?php echo $stats['accountsCount']; ?></h2>
                        <p class="card-text">Активных аккаунтов</p>
                        <a href="/accounts" class="btn btn-outline-light">Управление аккаунтами</a>
                    </div>
                </div>
            </div>
            
            <!-- Прокси -->
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-shield-alt dashboard-icon"></i>
                        <h5 class="card-title">Прокси</h5>
                        <h2><?php echo $stats['proxiesCount']; ?></h2>
                        <p class="card-text">Активных прокси</p>
                        <a href="/proxies" class="btn btn-outline-light">Управление прокси</a>
                    </div>
                </div>
            </div>
            
            <!-- Источники -->
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-spider dashboard-icon"></i>
                        <h5 class="card-title">Источники</h5>
                        <h2><?php echo $stats['sourcesCount']; ?></h2>
                        <p class="card-text">Активных источников</p>
                        <a href="/parsing" class="btn btn-outline-light">Настройки парсинга</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Оригинальный контент -->
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card bg-warning h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-file-alt dashboard-icon"></i>
                        <h5 class="card-title">Оригинальный контент</h5>
                        <h2><?php echo $stats['originalContentCount']; ?></h2>
                        <p class="card-text">Записей</p>
                    </div>
                </div>
            </div>
            
            <!-- Реврайтнутый контент -->
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card bg-danger text-white h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-pen-fancy dashboard-icon"></i>
                        <h5 class="card-title">Реврайтнутый контент</h5>
                        <h2><?php echo $stats['rewrittenContentCount']; ?></h2>
                        <p class="card-text">Записей</p>
                        <a href="/rewrite" class="btn btn-outline-light">Управление реврайтом</a>
                    </div>
                </div>
            </div>
            
            <!-- Опубликованные посты -->
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card bg-secondary text-white h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-share-alt dashboard-icon"></i>
                        <h5 class="card-title">Опубликовано</h5>
                        <h2><?php echo $stats['postedCount']; ?></h2>
                        <p class="card-text">Постов</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Последние реврайтнутые посты -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Последние реврайтнутые посты</h5>
            </div>
            <div class="card-body">
                <?php if (empty($stats['latestRewrittenContent'])): ?>
                <div class="alert alert-info">
                    Пока нет реврайтнутых постов.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Заголовок</th>
                                <th>Контент</th>
                                <th>Дата реврайта</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['latestRewrittenContent'] as $content): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($content['title'] ?? 'Без заголовка'); ?></td>
                                <td>
                                    <div class="content-preview">
                                        <?php echo htmlspecialchars(substr($content['content'], 0, 150) . (strlen($content['content']) > 150 ? '...' : '')); ?>
                                    </div>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($content['rewrite_date'])); ?></td>
                                <td>
                                    <?php
                                    switch ($content['status']) {
                                        case 'pending':
                                            echo '<span class="badge bg-warning">В ожидании</span>';
                                            break;
                                        case 'rewritten':
                                            echo '<span class="badge bg-success">Готово</span>';
                                            break;
                                        case 'posted':
                                            echo '<span class="badge bg-primary">Опубликовано</span>';
                                            break;
                                        case 'failed':
                                            echo '<span class="badge bg-danger">Ошибка</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Неизвестно</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="/rewrite/view/<?php echo $content['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Просмотр
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="/rewrite" class="btn btn-primary">Все реврайтнутые посты</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для подтверждения удаления -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Вы уверены, что хотите удалить <span id="deleteItemName"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Удалить</button>
            </div>
        </div>
    </div>
</div>
