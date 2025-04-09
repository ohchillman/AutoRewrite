<div class="row">
    <div class="col-md-12">
        <!-- Контейнер для toast-уведомлений -->
        <div class="toast-container position-fixed top-0 end-0 p-3"></div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Добавить новый источник</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addSourceForm" aria-expanded="false">
                    <i class="fas fa-plus"></i> Добавить источник
                </button>
            </div>
            <div class="card-body collapse" id="addSourceForm">
                <form action="/parsing/add" method="POST" class="ajax-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Название источника</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="form-text">Название для идентификации источника</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="url" class="form-label">URL источника</label>
                                <input type="text" class="form-control" id="url" name="url" required>
                                <div class="form-text">URL для парсинга контента</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="source_type" class="form-label">Тип источника</label>
                                <select class="form-select" id="source_type" name="source_type" required>
                                    <option value="">Выберите тип источника</option>
                                    <option value="rss">RSS-лента</option>
                                    <option value="blog">Новостной сайт</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="parsing_frequency" class="form-label">Частота парсинга (минуты)</label>
                                <input type="number" class="form-control" id="parsing_frequency" name="parsing_frequency" min="5" value="60">
                                <div class="form-text">Как часто проверять источник на новый контент</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="proxy_id" class="form-label">Прокси (опционально)</label>
                                <select class="form-select" id="proxy_id" name="proxy_id">
                                    <option value="">Без прокси</option>
                                    <?php foreach ($proxies as $proxy): ?>
                                    <option value="<?php echo $proxy['id']; ?>"><?php echo htmlspecialchars($proxy['ip'] . ':' . $proxy['port']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="mb-3 w-100 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Сохранить источник
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row source-fields rss-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для RSS</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rss_items" class="form-label">Количество записей</label>
                                <input type="number" class="form-control" id="rss_items" name="additional_settings[items]" min="1" max="100" value="20">
                                <div class="form-text">Количество записей для получения</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rss_full_content" class="form-label">Получать полный контент</label>
                                <select class="form-select" id="rss_full_content" name="additional_settings[full_content]">
                                    <option value="1">Да</option>
                                    <option value="0" selected>Нет</option>
                                </select>
                                <div class="form-text">Пытаться получить полный контент статьи (может занять больше времени)</div>
                            </div>
                        </div>
                    </div>

                    <div class="row source-fields blog-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для новостного сайта</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="blog_items" class="form-label">Количество записей</label>
                                <input type="number" class="form-control" id="blog_items" name="additional_settings[items]" min="1" max="50" value="20">
                                <div class="form-text">Количество записей для получения</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="blog_full_content" class="form-label">Получать полный контент</label>
                                <select class="form-select" id="blog_full_content" name="additional_settings[full_content]">
                                    <option value="1">Да</option>
                                    <option value="0" selected>Нет</option>
                                </select>
                                <div class="form-text">Пытаться получить полный контент статьи (может занять больше времени)</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="selectors_container" class="form-label">Селекторы для парсинга (опционально)</label>
                                <input type="text" class="form-control mb-2" id="selectors_container" name="additional_settings[selectors][container]" placeholder="XPath селектор для контейнеров статей, например: //article">
                                <input type="text" class="form-control mb-2" id="selectors_title" name="additional_settings[selectors][title]" placeholder="XPath селектор для заголовка, например: .//h1 | .//h2">
                                <input type="text" class="form-control mb-2" id="selectors_content" name="additional_settings[selectors][content]" placeholder="XPath селектор для контента, например: .//div[contains(@class, 'content')]">
                                <input type="text" class="form-control mb-2" id="selectors_link" name="additional_settings[selectors][link]" placeholder="XPath селектор для ссылки, например: .//a[contains(@class, 'read-more')] | .//h2/a">
                                <input type="text" class="form-control" id="selectors_date" name="additional_settings[selectors][date]" placeholder="XPath селектор для даты, например: .//time | .//span[contains(@class, 'date')]">
                                <div class="form-text">Оставьте пустыми для автоматического определения</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Список источников</h5>
                <div class="bulk-actions-sources" style="display: none;">
                    <button type="button" class="btn btn-danger btn-sm delete-selected-sources">
                        <i class="fas fa-trash"></i> Удалить выбранное
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($sources)): ?>
                <div class="alert alert-info">
                    Источники не найдены. Добавьте новый источник с помощью формы выше.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input select-all-sources" type="checkbox" value="" id="selectAllSources">
                                    </div>
                                </th>
                                <th>Название</th>
                                <th>URL</th>
                                <th>Тип</th>
                                <th>Частота</th>
                                <th>Прокси</th>
                                <th>Последний парсинг</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sources as $source): ?>
                            <tr class="source-row" data-id="<?php echo $source['id']; ?>">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input source-checkbox" type="checkbox" value="<?php echo $source['id']; ?>">
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($source['name']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($source['url']); ?>" target="_blank">
                                        <?php echo htmlspecialchars(substr($source['url'], 0, 30) . (strlen($source['url']) > 30 ? '...' : '')); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    switch ($source['source_type']) {
                                        case 'rss':
                                            echo '<span class="badge bg-info">RSS</span>';
                                            break;
                                        case 'blog':
                                            echo '<span class="badge bg-success">Новостной сайт</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Другое</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $source['parsing_frequency']; ?> мин</td>
                                <td>
                                    <?php if (!empty($source['proxy_ip'])): ?>
                                    <?php echo htmlspecialchars($source['proxy_ip'] . ':' . $source['proxy_port']); ?>
                                    <?php else: ?>
                                    <span class="text-muted">Нет</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($source['last_parsed']) {
                                        echo date('d.m.Y H:i', strtotime($source['last_parsed']));
                                    } else {
                                        echo '<span class="text-muted">Не парсился</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($source['is_active']): ?>
                                    <span class="badge bg-success">Активен</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Неактивен</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="/parsing/edit/<?php echo $source['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i> Редактировать
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="window.location.href='/parsing/parse/<?php echo $source['id']; ?>'">
                                            <i class="fas fa-sync-alt"></i> Парсить
                                        </button>
                                        <button type="button" class="btn btn-sm <?php echo $source['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                onclick="window.location.href='/parsing/toggle/<?php echo $source['id']; ?>'">
                                            <?php if ($source['is_active']): ?>
                                            <i class="fas fa-times"></i> Деактивировать
                                            <?php else: ?>
                                            <i class="fas fa-check"></i> Активировать
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                data-delete-url="/parsing/delete/<?php echo $source['id']; ?>"
                                                data-item-name="источник <?php echo htmlspecialchars($source['name']); ?>">
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
    // Инициализация модального окна
    const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    
    // Обработка кнопок удаления
    const deleteBtns = document.querySelectorAll('.delete-btn');
    deleteBtns.forEach(function(btn) {
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
    
    // Обработка выбора всех источников
    const selectAllSources = document.getElementById('selectAllSources');
    const sourceCheckboxes = document.querySelectorAll('.source-checkbox');
    const bulkActionsSources = document.querySelector('.bulk-actions-sources');
    
    if (selectAllSources) {
        selectAllSources.addEventListener('change', function() {
            const isChecked = this.checked;
            sourceCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            // Показываем/скрываем кнопки массовых действий
            if (isChecked && sourceCheckboxes.length > 0) {
                bulkActionsSources.style.display = 'block';
            } else {
                bulkActionsSources.style.display = 'none';
            }
        });
    }
    
    // Обработка выбора отдельных источников
    sourceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Проверяем, есть ли выбранные элементы
            const hasChecked = Array.from(sourceCheckboxes).some(cb => cb.checked);
            
            // Показываем/скрываем кнопки массовых действий
            bulkActionsSources.style.display = hasChecked ? 'block' : 'none';
            
            // Обновляем состояние "выбрать все"
            if (!hasChecked) {
                selectAllSources.checked = false;
            } else if (Array.from(sourceCheckboxes).every(cb => cb.checked)) {
                selectAllSources.checked = true;
            }
        });
    });
    
    // Обработка кнопки массового удаления источников
    const deleteSelectedSourcesBtn = document.querySelector('.delete-selected-sources');
    if (deleteSelectedSourcesBtn) {
        deleteSelectedSourcesBtn.addEventListener('click', function() {
            // Собираем ID выбранных элементов
            const selectedIds = Array.from(sourceCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                showNotification('Не выбрано ни одного элемента для удаления', 'warning');
                return;
            }
            
            // Настраиваем модальное окно
            document.getElementById('deleteItemName').textContent = `выбранные источники (${selectedIds.length} шт.)`;
            
            // Настраиваем кнопку подтверждения
            document.getElementById('confirmDeleteBtn').onclick = function() {
                // Скрываем модальное окно
                deleteConfirmModal.hide();
                
                // Отправляем запрос на массовое удаление
                fetch('/parsing/bulkDelete', {
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
    
    // Показывать/скрывать поля в зависимости от типа источника
    const sourceTypeSelect = document.getElementById('source_type');
    if (sourceTypeSelect) {
        sourceTypeSelect.addEventListener('change', function() {
            // Скрываем все поля
            document.querySelectorAll('.source-fields').forEach(function(field) {
                field.classList.add('d-none');
            });
            
            // Показываем нужные поля в зависимости от типа источника
            const sourceType = this.value.toLowerCase();
            
            if (sourceType === 'rss') {
                document.querySelector('.rss-fields').classList.remove('d-none');
            } else if (sourceType === 'blog') {
                document.querySelector('.blog-fields').classList.remove('d-none');
            }
        });
    }
    
    // Функция для отображения toast-уведомлений
    function showNotification(message, type) {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) return;
        
        // Создаем уникальный ID для toast
        const toastId = 'toast-' + Date.now();
        
        // Определяем цвет заголовка в зависимости от типа
        const headerClass = type === 'success' ? 'bg-success' : 
                          type === 'warning' ? 'bg-warning' : 'bg-danger';
        
        // Создаем элемент toast
        const toastDiv = document.createElement('div');
        toastDiv.id = toastId;
        toastDiv.className = 'toast fade show';
        toastDiv.setAttribute('role', 'alert');
        toastDiv.setAttribute('aria-live', 'assertive');
        toastDiv.setAttribute('aria-atomic', 'true');
        
        toastDiv.innerHTML = `
            <div class="toast-header ${headerClass} text-white">
                <strong class="me-auto">Уведомление</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        // Добавляем toast в контейнер
        toastContainer.appendChild(toastDiv);
        
        // Инициализируем toast
        const toast = new bootstrap.Toast(toastDiv, {
            autohide: true,
            delay: 5000
        });
        
        // Показываем toast
        toast.show();
        
        // Удаляем toast после скрытия
        toastDiv.addEventListener('hidden.bs.toast', function () {
            toastDiv.remove();
        });
    }
});
</script>
