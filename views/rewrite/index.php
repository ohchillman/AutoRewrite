<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Контент для реврайта</h5>
            </div>
            <div class="card-body">
                <?php if (empty($originalContent)): ?>
                <div class="alert alert-info">
                    Нет контента для реврайта. Добавьте источники в разделе "Настройки парсинга" и запустите парсинг.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Заголовок</th>
                                <th>Источник</th>
                                <th>Дата публикации</th>
                                <th>Дата парсинга</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($originalContent as $content): ?>
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold"><?php echo htmlspecialchars($content['title']); ?></span>
                                        <span class="text-muted small"><?php echo substr(strip_tags($content['content']), 0, 100) . '...'; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        switch ($content['source_type']) {
                                            case 'twitter': echo 'bg-info'; break;
                                            case 'linkedin': echo 'bg-primary'; break;
                                            case 'youtube': echo 'bg-danger'; break;
                                            case 'blog': echo 'bg-success'; break;
                                            case 'rss': echo 'bg-warning'; break;
                                            default: echo 'bg-secondary';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($content['source_name']); ?>
                                    </span>
                                    <div class="small mt-1">
                                        <a href="<?php echo htmlspecialchars($content['url']); ?>" target="_blank">
                                            Оригинал <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($content['published_date'])); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($content['parsed_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary rewrite-btn" data-content-id="<?php echo $content['id']; ?>">
                                        <i class="fas fa-sync-alt"></i> Реврайт
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Реврайтнутый контент</h5>
            </div>
            <div class="card-body">
                <?php if (empty($rewrittenContent)): ?>
                <div class="alert alert-info">
                    Нет реврайтнутого контента. Выполните реврайт контента из списка выше.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Заголовок</th>
                                <th>Источник</th>
                                <th>Дата реврайта</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rewrittenContent as $content): ?>
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold"><?php echo htmlspecialchars($content['title']); ?></span>
                                        <span class="text-muted small"><?php echo substr(strip_tags($content['content']), 0, 100) . '...'; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        switch ($content['source_type']) {
                                            case 'twitter': echo 'bg-info'; break;
                                            case 'linkedin': echo 'bg-primary'; break;
                                            case 'youtube': echo 'bg-danger'; break;
                                            case 'blog': echo 'bg-success'; break;
                                            case 'rss': echo 'bg-warning'; break;
                                            default: echo 'bg-secondary';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($content['source_name']); ?>
                                    </span>
                                    <div class="small mt-1">
                                        <a href="<?php echo htmlspecialchars($content['original_url']); ?>" target="_blank">
                                            Оригинал <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($content['rewrite_date'])); ?></td>
                                <td>
                                    <?php
                                    switch ($content['status']) {
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
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="/rewrite/view/<?php echo $content['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Просмотр
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                data-delete-url="/rewrite/delete/<?php echo $content['id']; ?>"
                                                data-item-name="контент '<?php echo htmlspecialchars($content['title']); ?>'">
                                            <i class="fas fa-trash"></i> Удалить
                                        </button>
                                    </div>
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
// Обработка кнопки реврайта
document.addEventListener('DOMContentLoaded', function() {
    const rewriteBtns = document.querySelectorAll('.rewrite-btn');
    const rewriteModal = new bootstrap.Modal(document.getElementById('rewriteModal'));
    
    rewriteBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
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
    });
    
    // Функция для отображения уведомлений
    function showNotification(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.querySelector('.container').prepend(alertDiv);
        
        // Автоматически скрываем уведомление через 5 секунд
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
    }
});
</script>
