<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Контент для реврайта</h5>
                <div class="bulk-actions-original" style="display: none;">
                    <button type="button" class="btn btn-danger btn-sm delete-selected-original">
                        <i class="fas fa-trash"></i> Удалить выбранное
                    </button>
                </div>
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
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input select-all-original" type="checkbox" value="" id="selectAllOriginal">
                                    </div>
                                </th>
                                <th>Заголовок</th>
                                <th>Источник</th>
                                <th>Дата публикации</th>
                                <th>Дата парсинга</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($originalContent as $content): ?>
                            <tr class="content-row" data-id="<?php echo $content['id']; ?>">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input original-content-checkbox" type="checkbox" value="<?php echo $content['id']; ?>">
                                    </div>
                                </td>
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
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-primary rewrite-btn" data-content-id="<?php echo $content['id']; ?>">
                                            <i class="fas fa-sync-alt"></i> Реврайт
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-original-btn" 
                                                data-delete-url="/rewrite/deleteOriginal/<?php echo $content['id']; ?>"
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

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Реврайтнутый контент</h5>
                <div class="bulk-actions-rewritten" style="display: none;">
                    <button type="button" class="btn btn-danger btn-sm delete-selected-rewritten">
                        <i class="fas fa-trash"></i> Удалить выбранное
                    </button>
                </div>
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
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input select-all-rewritten" type="checkbox" value="" id="selectAllRewritten">
                                    </div>
                                </th>
                                <th>Заголовок</th>
                                <th>Источник</th>
                                <th>Версий</th>
                                <th>Дата последнего реврайта</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rewrittenContent as $content): ?>
                            <tr class="content-row" data-id="<?php echo $content['id']; ?>">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input rewritten-content-checkbox" type="checkbox" value="<?php echo $content['id']; ?>">
                                    </div>
                                </td>
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
                                <td>
                                    <span class="badge bg-info"><?php echo $content['version_count']; ?></span>
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
                                        <a href="/rewrite/view/<?php echo $content['original_id']; ?>" class="btn btn-sm btn-info">
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
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация модальных окон
    const rewriteModal = new bootstrap.Modal(document.getElementById('rewriteModal'));
    const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    
    // Обработка кнопки реврайта
    const rewriteBtns = document.querySelectorAll('.rewrite-btn');
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
    
    // Обработка кнопок удаления оригинального контента
    const deleteOriginalBtns = document.querySelectorAll('.delete-original-btn');
    deleteOriginalBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const deleteUrl = this.getAttribute('data-delete-url');
            const itemName = this.getAttribute('data-item-name');
            
            // Настраиваем модальное окно
            document.getElementById('deleteItemName').textContent = itemName;
            
            // Настраиваем кнопку подтверждения
            document.getElementById('confirmDeleteBtn').onclick = function() {
                // Скрываем модальное окно
                deleteConfirmModal.hide();
                
                // Отправляем запрос на удаление
                fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
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
                    // Показываем сообщение об ошибке
                    showNotification('Произошла ошибка при удалении: ' + error.message, 'danger');
                    console.error('Delete error:', error);
                });
            };
            
            // Показываем модальное окно
            deleteConfirmModal.show();
        });
    });
    
    // Обработка выбора всех оригинальных контентов
    const selectAllOriginal = document.getElementById('selectAllOriginal');
    const originalCheckboxes = document.querySelectorAll('.original-content-checkbox');
    const bulkActionsOriginal = document.querySelector('.bulk-actions-original');
    
    if (selectAllOriginal) {
        selectAllOriginal.addEventListener('change', function() {
            const isChecked = this.checked;
            originalCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            // Показываем/скрываем кнопки массовых действий
            if (isChecked && originalCheckboxes.length > 0) {
                bulkActionsOriginal.style.display = 'block';
            } else {
                bulkActionsOriginal.style.display = 'none';
            }
        });
    }
    
    // Обработка выбора отдельных оригинальных контентов
    originalCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Проверяем, есть ли выбранные элементы
            const hasChecked = Array.from(originalCheckboxes).some(cb => cb.checked);
            
            // Показываем/скрываем кнопки массовых действий
            bulkActionsOriginal.style.display = hasChecked ? 'block' : 'none';
            
            // Обновляем состояние "выбрать все"
            if (!hasChecked) {
                selectAllOriginal.checked = false;
            } else if (Array.from(originalCheckboxes).every(cb => cb.checked)) {
                selectAllOriginal.checked = true;
            }
        });
    });
    
    // Обработка кнопки массового удаления оригинального контента
    const deleteSelectedOriginalBtn = document.querySelector('.delete-selected-original');
    if (deleteSelectedOriginalBtn) {
        deleteSelectedOriginalBtn.addEventListener('click', function() {
            // Собираем ID выбранных элементов
            const selectedIds = Array.from(originalCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                showNotification('Не выбрано ни одного элемента для удаления', 'warning');
                return;
            }
            
            // Настраиваем модальное окно
            document.getElementById('deleteItemName').textContent = `выбранные элементы (${selectedIds.length} шт.)`;
            
            // Настраиваем кнопку подтверждения
            document.getElementById('confirmDeleteBtn').onclick = function() {
                // Скрываем модальное окно
                deleteConfirmModal.hide();
                
                // Отправляем запрос на массовое удаление
                fetch('/rewrite/bulkDeleteOriginal', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ ids: selectedIds })
                })
                .then(response => response.json())
                .then(data => {
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
                    // Показываем сообщение об ошибке
                    showNotification('Произошла ошибка при массовом удалении: ' + error.message, 'danger');
                    console.error('Bulk delete error:', error);
                });
            };
            
            // Показываем модальное окно
            deleteConfirmModal.show();
        });
    }
    
    // Обработка выбора всех реврайтнутых контентов
    const selectAllRewritten = document.getElementById('selectAllRewritten');
    const rewrittenCheckboxes = document.querySelectorAll('.rewritten-content-checkbox');
    const bulkActionsRewritten = document.querySelector('.bulk-actions-rewritten');
    
    if (selectAllRewritten) {
        selectAllRewritten.addEventListener('change', function() {
            const isChecked = this.checked;
            rewrittenCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            // Показываем/скрываем кнопки массовых действий
            if (isChecked && rewrittenCheckboxes.length > 0) {
                bulkActionsRewritten.style.display = 'block';
            } else {
                bulkActionsRewritten.style.display = 'none';
            }
        });
    }
    
    // Обработка выбора отдельных реврайтнутых контентов
    rewrittenCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Проверяем, есть ли выбранные элементы
            const hasChecked = Array.from(rewrittenCheckboxes).some(cb => cb.checked);
            
            // Показываем/скрываем кнопки массовых действий
            bulkActionsRewritten.style.display = hasChecked ? 'block' : 'none';
            
            // Обновляем состояние "выбрать все"
            if (!hasChecked) {
                selectAllRewritten.checked = false;
            } else if (Array.from(rewrittenCheckboxes).every(cb => cb.checked)) {
                selectAllRewritten.checked = true;
            }
        });
    });
    
    // Обработка кнопки массового удаления реврайтнутого контента
    const deleteSelectedRewrittenBtn = document.querySelector('.delete-selected-rewritten');
    if (deleteSelectedRewrittenBtn) {
        deleteSelectedRewrittenBtn.addEventListener('click', function() {
            // Собираем ID выбранных элементов
            const selectedIds = Array.from(rewrittenCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                showNotification('Не выбрано ни одного элемента для удаления', 'warning');
                return;
            }
            
            // Настраиваем модальное окно
            document.getElementById('deleteItemName').textContent = `выбранные элементы (${selectedIds.length} шт.)`;
            
            // Настраиваем кнопку подтверждения
            document.getElementById('confirmDeleteBtn').onclick = function() {
                // Скрываем модальное окно
                deleteConfirmModal.hide();
                
                // Отправляем запрос на массовое удаление
                fetch('/rewrite/bulkDelete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ ids: selectedIds })
                })
                .then(response => response.json())
                .then(data => {
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
                    // Показываем сообщение об ошибке
                    showNotification('Произошла ошибка при массовом удалении: ' + error.message, 'danger');
                    console.error('Bulk delete error:', error);
                });
            };
            
            // Показываем модальное окно
            deleteConfirmModal.show();
        });
    }
});
</script>
