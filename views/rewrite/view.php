<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Оригинальный контент</h5>
                <div>
                    <a href="/rewrite" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад к списку
                    </a>
                    <button type="button" class="btn btn-sm btn-primary rewrite-btn" data-content-id="<?php echo $originalContent['id']; ?>">
                        <i class="fas fa-sync-alt"></i> Создать новую версию
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><?php echo htmlspecialchars($originalContent['title']); ?></h6>
                    <div class="content-box p-3 bg-light rounded mb-3">
                        <?php echo nl2br(htmlspecialchars($originalContent['content'])); ?>
                    </div>
                    <div class="small text-muted">
                        <div><strong>Источник:</strong> <?php echo htmlspecialchars($originalContent['source_name']); ?></div>
                        <div><strong>Автор:</strong> <?php echo htmlspecialchars($originalContent['author'] ?? 'Не указан'); ?></div>
                        <div><strong>Дата публикации:</strong> <?php echo date('d.m.Y H:i', strtotime($originalContent['published_date'])); ?></div>
                        <div>
                            <strong>URL:</strong> 
                            <a href="<?php echo htmlspecialchars($originalContent['url']); ?>" target="_blank">
                                <?php echo htmlspecialchars(substr($originalContent['url'], 0, 50) . (strlen($originalContent['url']) > 50 ? '...' : '')); ?>
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                        <div><strong>Количество реврайтов:</strong> <?php echo $originalContent['rewrite_count']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Реврайтнутые версии (<?php echo count($rewrittenVersions); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($rewrittenVersions)): ?>
                <div class="alert alert-info">
                    У этого контента еще нет реврайтнутых версий. Нажмите на кнопку "Создать новую версию", чтобы создать первую версию.
                </div>
                <?php else: ?>
                <ul class="nav nav-tabs mb-3" id="versionsTabs" role="tablist">
                    <?php foreach ($rewrittenVersions as $version): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $version['version_number'] == $selectedVersionNumber ? 'active' : ''; ?>" 
                                id="version-tab-<?php echo $version['version_number']; ?>" 
                                data-bs-toggle="tab" 
                                data-bs-target="#version-content-<?php echo $version['version_number']; ?>" 
                                type="button" 
                                role="tab">
                            Версия <?php echo $version['version_number']; ?> 
                            <span class="badge bg-secondary"><?php echo date('d.m.Y', strtotime($version['created_at'])); ?></span>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="tab-content" id="versionsTabsContent">
                    <?php foreach ($rewrittenVersions as $version): ?>
                    <div class="tab-pane fade <?php echo $version['version_number'] == $selectedVersionNumber ? 'show active' : ''; ?>" 
                         id="version-content-<?php echo $version['version_number']; ?>" 
                         role="tabpanel">
                        <div class="d-flex justify-content-between mb-2">
                            <h6><?php echo htmlspecialchars($version['title']); ?></h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-danger delete-version-btn" 
                                        data-delete-url="/rewrite/deleteVersion/<?php echo $version['id']; ?>"
                                        data-version-id="<?php echo $version['id']; ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteVersionModal">
                                    <i class="fas fa-trash"></i> Удалить версию
                                </button>
                            </div>
                        </div>
                        
                        <div class="content-box p-3 bg-light rounded mb-3">
                            <?php echo nl2br(htmlspecialchars($version['content'])); ?>
                        </div>
                        
                        <div class="small text-muted">
                            <div><strong>Дата реврайта:</strong> <?php echo date('d.m.Y H:i', strtotime($version['created_at'])); ?></div>
                            <div>
                                <strong>Статус:</strong> 
                                <?php
                                switch ($version['status']) {
                                    case 'rewritten':
                                        echo '<span class="badge bg-success">Реврайтнут</span>';
                                        break;
                                    case 'posted':
                                        echo '<span class="badge bg-primary">Опубликован</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-secondary">Ожидает</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Форма для публикации этой версии -->
                        <div class="mt-3 pt-3 border-top">
                            <h6>Опубликовать эту версию</h6>
                            <?php if (empty($accounts)): ?>
                            <div class="alert alert-warning">
                                Нет активных аккаунтов для публикации. Добавьте аккаунты в разделе "Аккаунты".
                            </div>
                            <?php else: ?>
                            <form action="/rewrite/publishPost" method="POST" class="ajax-form">
                                <input type="hidden" name="rewritten_id" value="<?php echo $mainRewrittenContent['id']; ?>">
                                <input type="hidden" name="version_id" value="<?php echo $version['id']; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <select class="form-select" name="account_id" required>
                                                <option value="">Выберите аккаунт</option>
                                                <?php foreach ($accounts as $account): ?>
                                                <option value="<?php echo $account['id']; ?>">
                                                    <?php echo htmlspecialchars($account['name'] . ' (' . $account['account_type_name'] . ')'); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Опубликовать
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">История публикаций</h5>
            </div>
            <div class="card-body">
                <?php if (empty($posts)): ?>
                <div class="alert alert-info">
                    Этот контент еще не был опубликован ни в один аккаунт.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Версия</th>
                                <th>Аккаунт</th>
                                <th>Тип</th>
                                <th>Дата публикации</th>
                                <th>Статус</th>
                                <th>Ссылка</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                            <tr>
                                <td>
                                    <a href="/rewrite/view/<?php echo $originalContent['id']; ?>?version=<?php echo $post['version_number']; ?>">
                                        Версия #<?php echo $post['version_number']; ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($post['account_name']); ?></td>
                                <td>
                                    <?php
                                    switch ($post['account_type_name']) {
                                        case 'Twitter':
                                            echo '<span class="badge bg-info">Twitter</span>';
                                            break;
                                        case 'LinkedIn':
                                            echo '<span class="badge bg-primary">LinkedIn</span>';
                                            break;
                                        case 'YouTube':
                                            echo '<span class="badge bg-danger">YouTube</span>';
                                            break;
                                        case 'Threads':
                                            echo '<span class="badge bg-success">Threads</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">' . htmlspecialchars($post['account_type_name']) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($post['posted_at'])); ?></td>
                                <td>
                                    <?php
                                    switch ($post['status']) {
                                        case 'posted':
                                            echo '<span class="badge bg-success">Опубликован</span>';
                                            break;
                                        case 'failed':
                                            echo '<span class="badge bg-danger">Ошибка</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="badge bg-warning">В очереди</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Неизвестно</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($post['post_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($post['post_url']); ?>" target="_blank">
                                        Просмотреть <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">Недоступно</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для подтверждения удаления версии -->
<div class="modal fade" id="deleteVersionModal" tabindex="-1" aria-labelledby="deleteVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteVersionModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить эту версию реврайтнутого контента?</p>
                <p class="text-danger">Внимание: это также удалит все записи о публикациях для этой версии.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteVersionBtn">Удалить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для процесса реврайта -->
<div class="modal fade" id="rewriteModal" tabindex="-1" aria-labelledby="rewriteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rewriteModalLabel">Реврайт контента</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Выполняется реврайт контента. Пожалуйста, подождите...</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Уведомления об успехе/ошибке -->
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка кнопки реврайта
    const rewriteBtn = document.querySelector('.rewrite-btn');
    const rewriteModal = new bootstrap.Modal(document.getElementById('rewriteModal'));
    
    if (rewriteBtn) {
        rewriteBtn.addEventListener('click', function() {
            const contentId = this.getAttribute('data-content-id');
            
            // Показываем модальное окно с прогрессом
            rewriteModal.show();
            
            // Отправляем запрос на реврайт
            fetch('/rewrite/process/' + contentId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ contentId: contentId })
            })
            .then(response => response.json())
            .then(data => {
                rewriteModal.hide();
                
                if (data.success) {
                    // Показываем уведомление об успехе
                    showNotification(data.message, 'success');
                    
                    // Если есть редирект, переходим по нему
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else if (data.refresh) {
                        window.location.reload();
                    }
                } else {
                    // Показываем уведомление об ошибке
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                rewriteModal.hide();
                showNotification('Произошла ошибка при обработке запроса', 'danger');
                console.error('Error:', error);
            });
        });
    }
    
    // Обработка кнопки удаления версии
    const deleteVersionBtns = document.querySelectorAll('.delete-version-btn');
    const confirmDeleteVersionBtn = document.getElementById('confirmDeleteVersionBtn');
    
    if (deleteVersionBtns.length > 0 && confirmDeleteVersionBtn) {
        deleteVersionBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const deleteUrl = this.getAttribute('data-delete-url');
                const versionId = this.getAttribute('data-version-id');
                
                // Настраиваем кнопку подтверждения
                confirmDeleteVersionBtn.onclick = function() {
                    // Скрываем модальное окно
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteVersionModal'));
                    modal.hide();
                    
                    // Показываем индикатор загрузки
                    showLoading();
                    
                    // Отправляем запрос на удаление
                    fetch(deleteUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Скрываем индикатор загрузки
                        hideLoading();
                        
                        // Показываем сообщение
                        showNotification(data.message, data.success ? 'success' : 'danger');
                        
                        // Если успешно, обновляем страницу или перенаправляем
                        if (data.success) {
                            if (data.redirect) {
                                setTimeout(function() {
                                    window.location.href = data.redirect;
                                }, 1000);
                            } else {
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
                            }
                        }
                    })
                    .catch(error => {
                        // Скрываем индикатор загрузки
                        hideLoading();
                        
                        // Показываем сообщение об ошибке
                        showNotification('Произошла ошибка при удалении версии: ' + error.message, 'danger');
                        console.error('Delete error:', error);
                    });
                };
            });
        });
    }
    
    // Используем глобальную функцию showNotification из main.js
    
    // Функции для отображения/скрытия индикатора загрузки
    function showLoading() {
        // Создаем элемент оверлея, если его еще нет
        if (!document.getElementById('loadingOverlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'spinner-overlay';
            overlay.innerHTML = `
                <div class="spinner-container">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <div class="mt-2">Пожалуйста, подождите...</div>
                </div>
            `;
            document.body.appendChild(overlay);
        } else {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
    }
    
    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
});
</script>