<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Добавить новый прокси</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addProxyForm" aria-expanded="false">
                    <i class="fas fa-plus"></i> Добавить прокси
                </button>
            </div>
            <div class="card-body collapse" id="addProxyForm">
                <form action="/proxies/add" method="POST" class="ajax-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Название прокси</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="ip" class="form-label">IP адрес</label>
                                <input type="text" class="form-control" id="ip" name="ip" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="port" class="form-label">Порт</label>
                                <input type="number" class="form-control" id="port" name="port" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="protocol" class="form-label">Протокол</label>
                                <select class="form-select" id="protocol" name="protocol" required>
                                    <option value="http">HTTP</option>
                                    <option value="https">HTTPS</option>
                                    <option value="socks4">SOCKS4</option>
                                    <option value="socks5">SOCKS5</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="username" class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" id="username" name="username">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="country" class="form-label">Страна</label>
                                <input type="text" class="form-control" id="country" name="country">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="ip_change_url" class="form-label">URL для смены IP</label>
                                <input type="text" class="form-control" id="ip_change_url" name="ip_change_url" placeholder="http://...">
                                <div class="form-text">Необязательное поле. URL для запроса смены IP.</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить прокси
                            </button>
                        </div>
                    </div>
                </form>
            </div>      
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Список прокси</h5>
                <div class="bulk-actions-proxies" style="display: none;">
                    <button type="button" class="btn btn-danger btn-sm delete-selected-proxies">
                        <i class="fas fa-trash"></i> Удалить выбранное
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($proxies)): ?>
                <div class="alert alert-info">
                    Прокси не найдены. Добавьте новый прокси с помощью формы выше.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <div class="form-check">
                                    <input class="form-check-input select-all-proxies" type="checkbox" value="" id="selectAllProxies">
                                </div>
                            </th>
                            <th>Название</th>
                            <th>IP:Порт</th>
                            <th>Протокол</th>
                            <th>Аутентификация</th>
                            <th>Страна</th>
                            <th>Статус</th>
                            <th>Последняя проверка</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proxies as $proxy): ?>
                        <tr class="proxy-list-item <?php 
                            if ($proxy['status'] === 'working') echo 'proxy-working';
                            elseif ($proxy['status'] === 'failed') echo 'proxy-failed';
                            else echo 'proxy-unchecked';
                        ?>" data-id="<?php echo $proxy['id']; ?>">
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input proxy-checkbox" type="checkbox" value="<?php echo $proxy['id']; ?>">
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($proxy['name']); ?></td>
                            <td><?php echo htmlspecialchars($proxy['ip'] . ':' . $proxy['port']); ?></td>
                            <td><?php echo htmlspecialchars(strtoupper($proxy['protocol'])); ?></td>
                            <td>
                                <?php if (!empty($proxy['username']) && !empty($proxy['password'])): ?>
                                <span class="badge bg-success">Есть</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Нет</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($proxy['country'] ?? 'Не указано'); ?></td>
                            <td id="proxy-status-<?php echo $proxy['id']; ?>">
                                <?php
                                switch ($proxy['status']) {
                                    case 'working':
                                        echo '<span class="badge bg-success">Работает</span>';
                                        break;
                                    case 'failed':
                                        echo '<span class="badge bg-danger">Не работает</span>';
                                        break;
                                    default:
                                        echo '<span class="badge bg-warning">Не проверен</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($proxy['last_check']) {
                                    echo date('d.m.Y H:i', strtotime($proxy['last_check']));
                                } else {
                                    echo 'Не проверялся';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">

                                    <button type="button" class="btn btn-sm btn-info check-proxy-btn" data-proxy-id="<?php echo $proxy['id']; ?>">
                                        <i class="fas fa-sync-alt"></i> Проверить
                                    </button>
                                    
                                    <?php if (!empty($proxy['ip_change_url'])): ?>
                                    <button type="button" class="btn btn-sm btn-warning change-ip-btn" data-proxy-id="<?php echo $proxy['id']; ?>" data-url="<?php echo htmlspecialchars($proxy['ip_change_url']); ?>">
                                        <i class="fas fa-exchange-alt"></i> Сменить IP
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-warning" disabled>
                                        <i class="fas fa-exchange-alt"></i> Сменить IP
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm <?php echo $proxy['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                            onclick="window.location.href='/proxies/toggle/<?php echo $proxy['id']; ?>'">
                                        <?php if ($proxy['is_active']): ?>
                                        <i class="fas fa-times"></i> Деактивировать
                                        <?php else: ?>
                                        <i class="fas fa-check"></i> Активировать
                                        <?php endif; ?>
                                    </button>

                                    <a href="/proxies/edit/<?php echo $proxy['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </a>

                                    <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                            data-delete-url="/proxies/delete/<?php echo $proxy['id']; ?>"
                                            data-item-name="прокси <?php echo htmlspecialchars($proxy['name']); ?>">
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
                    showToast(data.success ? 'success' : 'error', data.message);
                    
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
                    showToast('error', 'Произошла ошибка при удалении: ' + error.message);
                    console.error('Delete error:', error);
                });
            };
            
            // Показываем модальное окно
            deleteConfirmModal.show();
        });
    });
    
    // Обработка выбора всех прокси
    const selectAllProxies = document.getElementById('selectAllProxies');
    const proxyCheckboxes = document.querySelectorAll('.proxy-checkbox');
    const bulkActionsProxies = document.querySelector('.bulk-actions-proxies');
    
    if (selectAllProxies) {
        selectAllProxies.addEventListener('change', function() {
            const isChecked = this.checked;
            proxyCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            // Показываем/скрываем кнопки массовых действий
            if (isChecked && proxyCheckboxes.length > 0) {
                bulkActionsProxies.style.display = 'block';
            } else {
                bulkActionsProxies.style.display = 'none';
            }
        });
    }
    
    // Обработка выбора отдельных прокси
    proxyCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Проверяем, есть ли выбранные элементы
            const hasChecked = Array.from(proxyCheckboxes).some(cb => cb.checked);
            
            // Показываем/скрываем кнопки массовых действий
            bulkActionsProxies.style.display = hasChecked ? 'block' : 'none';
            
            // Обновляем состояние "выбрать все"
            if (!hasChecked) {
                selectAllProxies.checked = false;
            } else if (Array.from(proxyCheckboxes).every(cb => cb.checked)) {
                selectAllProxies.checked = true;
            }
        });
    });
    
    // Обработка кнопки массового удаления прокси
    const deleteSelectedProxiesBtn = document.querySelector('.delete-selected-proxies');
    if (deleteSelectedProxiesBtn) {
        deleteSelectedProxiesBtn.addEventListener('click', function() {
            // Собираем ID выбранных элементов
            const selectedIds = Array.from(proxyCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                showToast('warning', 'Не выбрано ни одного элемента для удаления');
                return;
            }
            
            // Настраиваем модальное окно
            document.getElementById('deleteItemName').textContent = `выбранные прокси (${selectedIds.length} шт.)`;
            
            // Настраиваем кнопку подтверждения
            document.getElementById('confirmDeleteBtn').onclick = function() {
                // Скрываем модальное окно
                deleteConfirmModal.hide();
                
                // Отправляем запрос на массовое удаление
                fetch('/proxies/bulkDelete', {
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
                    showToast(data.success ? 'success' : 'error', data.message);
                    
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
                    showToast('error', 'Произошла ошибка при массовом удалении: ' + error.message);
                    console.error('Bulk delete error:', error);
                });
            };
            
            // Показываем модальное окно
            deleteConfirmModal.show();
        });
    }
    
    // Обработка кнопки смены IP
    const changeIpButtons = document.querySelectorAll('.change-ip-btn');
    
    changeIpButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const proxyId = this.getAttribute('data-proxy-id');
            const url = this.getAttribute('data-url');
            
            // Блокируем кнопку и показываем индикатор загрузки
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Смена IP...';
            this.disabled = true;
            
            // Отправляем запрос на смену IP
            fetch('/proxies/changeIp/' + proxyId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Восстанавливаем кнопку
                this.innerHTML = '<i class="fas fa-exchange-alt"></i> Сменить IP';
                this.disabled = false;
                
                // Обновляем статус прокси
                const statusElement = document.querySelector('#proxy-status-' + proxyId);
                if (statusElement) {
                    statusElement.innerHTML = '<span class="badge bg-warning">Не проверен</span>';
                }
                
                // Показываем сообщение
                showToast(data.success ? 'success' : 'error', data.message);
            })
            .catch(error => {
                // Восстанавливаем кнопку
                this.innerHTML = '<i class="fas fa-exchange-alt"></i> Сменить IP';
                this.disabled = false;
                
                // Показываем сообщение об ошибке с подробностями
                showToast('error', 'Произошла ошибка при смене IP: ' + error.message);
                console.error('IP change error:', error);
            });
        });
    });
    
    // Примечание: Обработка кнопок проверки прокси перенесена в main.js
    // для избежания дублирования обработчиков событий
});
</script>
