<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Добавить новый аккаунт</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addAccountForm" aria-expanded="false">
                    <i class="fas fa-plus"></i> Добавить аккаунт
                </button>
            </div>
            <div class="card-body collapse" id="addAccountForm">
                <form action="/accounts/add" method="POST" class="ajax-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="account_type_id" class="form-label">Тип аккаунта</label>
                                <select class="form-select" id="account_type_id" name="account_type_id" required>
                                    <option value="">Выберите тип аккаунта</option>
                                    <?php foreach ($accountTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Название аккаунта</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="form-text">Название для идентификации аккаунта</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="proxy_id" class="form-label">Прокси</label>
                                <div class="input-group">
                                    <select class="form-select" id="proxy_id" name="proxy_id">
                                        <option value="">Без прокси</option>
                                        <?php foreach ($proxies as $proxy): ?>
                                        <option value="<?php echo $proxy['id']; ?>" 
                                            data-status="<?php echo htmlspecialchars($proxy['status']); ?>"
                                            data-ip="<?php echo htmlspecialchars($proxy['ip']); ?>"
                                            data-port="<?php echo htmlspecialchars($proxy['port']); ?>"
                                            data-protocol="<?php echo htmlspecialchars($proxy['protocol']); ?>">
                                            <?php echo htmlspecialchars($proxy['ip'] . ':' . $proxy['port']); ?>
                                            <?php if($proxy['status'] == 'working'): ?>
                                                <span class="text-success">✓</span>
                                            <?php elseif($proxy['status'] == 'failed'): ?>
                                                <span class="text-danger">✗</span>
                                            <?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-outline-secondary" type="button" id="testProxyBtn" disabled>
                                        <i class="fas fa-sync-alt"></i> Проверить
                                    </button>
                                </div>
                                <div id="proxyStatus" class="form-text mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row account-fields twitter-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Данные для Twitter</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="api_key" class="form-label">API Key</label>
                                <input type="text" class="form-control" id="api_key" name="api_key">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="api_secret" class="form-label">API Secret</label>
                                <input type="text" class="form-control" id="api_secret" name="api_secret">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="access_token" class="form-label">Access Token</label>
                                <input type="text" class="form-control" id="access_token" name="access_token">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="access_token_secret" class="form-label">Access Token Secret</label>
                                <input type="text" class="form-control" id="access_token_secret" name="access_token_secret">
                            </div>
                        </div>
                    </div>

                    <div class="row account-fields linkedin-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Данные для LinkedIn</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Email/Логин</label>
                                <input type="text" class="form-control" id="username" name="username">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="access_token" class="form-label">Access Token (опционально)</label>
                                <input type="text" class="form-control" id="access_token" name="access_token">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="refresh_token" class="form-label">Refresh Token (опционально)</label>
                                <input type="text" class="form-control" id="refresh_token" name="refresh_token">
                            </div>
                        </div>
                    </div>

                    <div class="row account-fields youtube-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Данные для YouTube</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="api_key" class="form-label">API Key</label>
                                <input type="text" class="form-control" id="api_key" name="api_key">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client ID</label>
                                <input type="text" class="form-control" id="client_id" name="additional_data[client_id]">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="client_secret" class="form-label">Client Secret</label>
                                <input type="text" class="form-control" id="client_secret" name="additional_data[client_secret]">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="refresh_token" class="form-label">Refresh Token</label>
                                <input type="text" class="form-control" id="refresh_token" name="refresh_token">
                            </div>
                        </div>
                    </div>

                    <div class="row account-fields threads-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Данные для Threads (Selenium)</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" id="username" name="username">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="user_agent" class="form-label">User Agent (опционально)</label>
                                <input type="text" class="form-control" id="user_agent" name="additional_data[user_agent]">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Проверка аккаунта</h6>
                                    <p class="card-text">После сохранения аккаунта вы сможете проверить его работоспособность с выбранным прокси</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить аккаунт
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Список аккаунтов</h5>
                <div class="d-flex">
                    <div class="bulk-actions-accounts me-2" style="display: none;">
                        <button type="button" class="btn btn-danger btn-sm delete-selected-accounts">
                            <i class="fas fa-trash"></i> Удалить выбранное
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-info btn-sm verify-all-accounts">
                            <i class="fas fa-check-circle"></i> Проверить все аккаунты
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($accounts)): ?>
                <div class="alert alert-info">
                    Аккаунты не найдены. Добавьте новый аккаунт с помощью формы выше.
                </div>
                <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input select-all-accounts" type="checkbox" value="" id="selectAllAccounts">
                                    </div>
                                </th>
                                <th>Название</th>
                                <th>Тип</th>
                                <th>Логин</th>
                                <th>Прокси</th>
                                <th>Статус</th>
                                <th>Проверка</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                            <tr class="account-row" data-id="<?php echo $account['id']; ?>">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input account-checkbox" type="checkbox" value="<?php echo $account['id']; ?>">
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($account['name']); ?></td>
                                <td><?php echo htmlspecialchars($account['account_type_name']); ?></td>
                                <td><?php echo htmlspecialchars($account['username'] ?: '-'); ?></td>
<td>
    <div class="input-group input-group-sm">
        <select class="form-select form-select-sm proxy-select" data-account-id="<?php echo $account['id']; ?>">
            <option value="">Без прокси</option>
            <?php foreach ($proxies as $proxy): ?>
            <option value="<?php echo $proxy['id']; ?>" 
                <?php echo (!empty($account['proxy_id']) && $account['proxy_id'] == $proxy['id']) ? 'selected' : ''; ?>>
                <?php if(isset($proxy['status'])): ?>
                    <?php if($proxy['status'] == 'working'): ?>
                        🟢
                    <?php elseif($proxy['status'] == 'failed'): ?>
                        🔴
                    <?php else: ?>
                        🟡
                    <?php endif; ?>
                <?php else: ?>
                    ⚪
                <?php endif; ?>
                <?php echo htmlspecialchars($proxy['ip'] . ':' . $proxy['port']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary btn-sm save-proxy-btn" type="button" data-account-id="<?php echo $account['id']; ?>">
            <i class="fas fa-save"></i>
        </button>
    </div>
    <div class="proxy-status-message-<?php echo $account['id']; ?> mt-1 small"></div>
    
    <!-- <?php if (!empty($account['proxy_id']) && !empty($account['proxy_ip'])): ?>
        <div class="mt-1 small">
            <?php if(isset($account['proxy_status'])): ?>
                <?php if($account['proxy_status'] == 'working'): ?>
                    <span class="badge bg-success">Работает</span>
                <?php elseif($account['proxy_status'] == 'failed'): ?>
                    <span class="badge bg-danger">Не работает</span>
                <?php else: ?>
                    <span class="badge bg-warning">Не проверен</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge bg-secondary">Статус неизвестен</span>
            <?php endif; ?>
        </div>
    <?php endif; ?> -->
</td>
                                <td>
                                    <span class="badge <?php echo $account['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $account['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info verify-account-btn" data-id="<?php echo $account['id']; ?>">
                                        <i class="fas fa-check-circle"></i> Проверить
                                    </button>
                                    <div class="account-verification-status-<?php echo $account['id']; ?> mt-1"></div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="/accounts/edit/<?php echo $account['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i> Редактировать
                                        </a>
                                        <button type="button" class="btn btn-sm <?php echo $account['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                onclick="window.location.href='/accounts/toggle/<?php echo $account['id']; ?>'">
                                            <?php if ($account['is_active']): ?>
                                            <i class="fas fa-times"></i> Деактивировать
                                            <?php else: ?>
                                            <i class="fas fa-check"></i> Активировать
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                data-delete-url="/accounts/delete/<?php echo $account['id']; ?>"
                                                data-item-name="аккаунт <?php echo htmlspecialchars($account['name']); ?>">
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
    // Инициализация модального окна для подтверждения удаления
    const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    
    // Обработка кнопок удаления
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const deleteUrl = this.getAttribute('data-delete-url');
            const itemName = this.getAttribute('data-item-name');
            
            document.getElementById('deleteItemName').textContent = itemName;
            document.getElementById('confirmDeleteBtn').onclick = function() {
                deleteConfirmModal.hide();
                
                fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'danger');
                    
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
                    showNotification('Произошла ошибка при удалении: ' + error.message, 'danger');
                    console.error('Delete error:', error);
                });
            };
            
            deleteConfirmModal.show();
        });
    });
    
    // Обработка выбора "выбрать все"
    const selectAllAccounts = document.querySelector('.select-all-accounts');
    const accountCheckboxes = document.querySelectorAll('.account-checkbox');
    const bulkActionsAccounts = document.querySelector('.bulk-actions-accounts');
    
    if (selectAllAccounts) {
        selectAllAccounts.addEventListener('change', function() {
            const isChecked = this.checked;
            
            accountCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            // Показываем/скрываем кнопки массовых действий
            if (isChecked && accountCheckboxes.length > 0) {
                bulkActionsAccounts.style.display = 'block';
            } else {
                bulkActionsAccounts.style.display = 'none';
            }
        });
    }
    
    // Обработка выбора отдельных аккаунтов
    accountCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Проверяем, есть ли выбранные элементы
            const hasChecked = Array.from(accountCheckboxes).some(cb => cb.checked);
            
            // Показываем/скрываем кнопки массовых действий
            bulkActionsAccounts.style.display = hasChecked ? 'block' : 'none';
            
            // Обновляем состояние "выбрать все"
            if (!hasChecked) {
                selectAllAccounts.checked = false;
            } else if (Array.from(accountCheckboxes).every(cb => cb.checked)) {
                selectAllAccounts.checked = true;
            }
        });
    });
    
    // Обработка кнопки массового удаления аккаунтов
    const deleteSelectedAccountsBtn = document.querySelector('.delete-selected-accounts');
    if (deleteSelectedAccountsBtn) {
        deleteSelectedAccountsBtn.addEventListener('click', function() {
            // Собираем ID выбранных элементов
            const selectedIds = Array.from(accountCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                showNotification('Не выбрано ни одного элемента для удаления', 'warning');
                return;
            }
            
            // Настраиваем модальное окно
            document.getElementById('deleteItemName').textContent = `выбранные аккаунты (${selectedIds.length} шт.)`;
            
            // Настраиваем кнопку подтверждения
            document.getElementById('confirmDeleteBtn').onclick = function() {
                // Скрываем модальное окно
                deleteConfirmModal.hide();
                
                // Отправляем запрос на массовое удаление
                fetch('/accounts/bulkDelete', {
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
    
    // Показывать/скрывать поля в зависимости от типа аккаунта
    const accountTypeSelect = document.getElementById('account_type_id');
    if (accountTypeSelect) {
        accountTypeSelect.addEventListener('change', function() {
            // Скрываем все поля
            document.querySelectorAll('.account-fields').forEach(function(field) {
                field.classList.add('d-none');
            });
            
            // Показываем нужные поля в зависимости от типа аккаунта
            const accountType = this.options[this.selectedIndex].text.toLowerCase();
            
            if (accountType.includes('twitter')) {
                document.querySelector('.twitter-fields').classList.remove('d-none');
            } else if (accountType.includes('linkedin')) {
                document.querySelector('.linkedin-fields').classList.remove('d-none');
            } else if (accountType.includes('youtube')) {
                document.querySelector('.youtube-fields').classList.remove('d-none');
            } else if (accountType.includes('threads')) {
                document.querySelector('.threads-fields').classList.remove('d-none');
            }
        });
    }
    
    // Обработка изменения выбора прокси в форме добавления
    const proxySelect = document.getElementById('proxy_id');
    const testProxyBtn = document.getElementById('testProxyBtn');
    const proxyStatus = document.getElementById('proxyStatus');
    
    if (proxySelect) {
        // Функция для обновления статуса прокси
        function updateProxyStatus() {
            const selectedOption = proxySelect.options[proxySelect.selectedIndex];
            if (selectedOption.value) {
                const status = selectedOption.getAttribute('data-status');
                if (status === 'working') {
                    proxyStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Прокси работает</span>';
                } else if (status === 'failed') {
                    proxyStatus.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Прокси не работает</span>';
                } else {
                    proxyStatus.innerHTML = '<span class="text-warning"><i class="fas fa-question-circle"></i> Статус прокси неизвестен</span>';
                }
                testProxyBtn.disabled = false;
            } else {
                proxyStatus.innerHTML = '';
                testProxyBtn.disabled = true;
            }
        }
        
        // Инициализация статуса прокси
        updateProxyStatus();
        
        // Обработчик изменения выбора прокси
        proxySelect.addEventListener('change', updateProxyStatus);
        
        // Обработчик кнопки проверки прокси
        testProxyBtn.addEventListener('click', function() {
            const proxyId = proxySelect.value;
            if (!proxyId) return;
            
            // Показываем индикатор загрузки
            proxyStatus.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Проверка прокси...</span>';
            testProxyBtn.disabled = true;
            
            // Отправляем запрос на проверку прокси
            fetch('/proxies/test/' + proxyId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    proxyStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
                } else {
                    proxyStatus.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ' + data.message + '</span>';
                }
                testProxyBtn.disabled = false;
            })
            .catch(error => {
                proxyStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Ошибка при проверке прокси</span>';
                testProxyBtn.disabled = false;
                console.error('Proxy test error:', error);
            });
        });
    }
    
    // Обработчик кнопок проверки аккаунта
    document.querySelectorAll('.verify-account-btn').forEach(button => {
        button.addEventListener('click', function() {
            const accountId = this.getAttribute('data-id');
            const statusContainer = document.querySelector('.account-verification-status-' + accountId);
            
            // Показываем индикатор загрузки
            statusContainer.innerHTML = '<div class="spinner-border spinner-border-sm text-info" role="status"><span class="visually-hidden">Загрузка...</span></div> Проверка...';
            this.disabled = true;
            
            // Отправляем запрос на проверку аккаунта
            fetch('/accounts/verify/' + accountId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusContainer.innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Аккаунт работает</span>';
                } else {
                    statusContainer.innerHTML = '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Ошибка</span>';
                    if (data.message) {
                        statusContainer.innerHTML += '<div class="small text-danger mt-1">' + data.message + '</div>';
                    }
                }
                this.disabled = false;
            })
            .catch(error => {
                statusContainer.innerHTML = '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Ошибка проверки</span>';
                this.disabled = false;
                console.error('Account verification error:', error);
            });
        });
    });
    
    // Обработчик кнопки проверки всех аккаунтов
    const verifyAllAccountsBtn = document.querySelector('.verify-all-accounts');
    if (verifyAllAccountsBtn) {
        verifyAllAccountsBtn.addEventListener('click', function() {
            // Получаем все кнопки проверки аккаунтов
            const verifyButtons = document.querySelectorAll('.verify-account-btn');
            if (verifyButtons.length === 0) return;
            
            // Отключаем кнопку проверки всех аккаунтов
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Проверка...';
            
            // Счетчик для отслеживания завершения всех проверок
            let completedChecks = 0;
            
            // Функция для проверки одного аккаунта
            function verifyAccount(button) {
                const accountId = button.getAttribute('data-id');
                const statusContainer = document.querySelector('.account-verification-status-' + accountId);
                
                // Показываем индикатор загрузки
                statusContainer.innerHTML = '<div class="spinner-border spinner-border-sm text-info" role="status"><span class="visually-hidden">Загрузка...</span></div> Проверка...';
                button.disabled = true;
                
                // Отправляем запрос на проверку аккаунта
                fetch('/accounts/verify/' + accountId, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusContainer.innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Аккаунт работает</span>';
                    } else {
                        statusContainer.innerHTML = '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Ошибка</span>';
                        if (data.message) {
                            statusContainer.innerHTML += '<div class="small text-danger mt-1">' + data.message + '</div>';
                        }
                    }
                    button.disabled = false;
                    
                    // Увеличиваем счетчик завершенных проверок
                    completedChecks++;
                    
                    // Если все проверки завершены, разблокируем кнопку проверки всех аккаунтов
                    if (completedChecks === verifyButtons.length) {
                        verifyAllAccountsBtn.disabled = false;
                        verifyAllAccountsBtn.innerHTML = '<i class="fas fa-check-circle"></i> Проверить все аккаунты';
                        showNotification('Проверка всех аккаунтов завершена', 'success');
                    }
                })
                .catch(error => {
                    statusContainer.innerHTML = '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Ошибка проверки</span>';
                    button.disabled = false;
                    console.error('Account verification error:', error);
                    
                    // Увеличиваем счетчик завершенных проверок
                    completedChecks++;
                    
                    // Если все проверки завершены, разблокируем кнопку проверки всех аккаунтов
                    if (completedChecks === verifyButtons.length) {
                        verifyAllAccountsBtn.disabled = false;
                        verifyAllAccountsBtn.innerHTML = '<i class="fas fa-check-circle"></i> Проверить все аккаунты';
                        showNotification('Проверка всех аккаунтов завершена', 'success');
                    }
                });
            }
            
            // Запускаем проверку для каждого аккаунта с небольшой задержкой
            verifyButtons.forEach((button, index) => {
                setTimeout(() => {
                    verifyAccount(button);
                }, index * 500); // Задержка 500 мс между запросами
            });
        });
    }
});
// Обработка изменения прокси через выпадающий список
document.querySelectorAll('.save-proxy-btn').forEach(button => {
    button.addEventListener('click', function() {
        const accountId = this.getAttribute('data-account-id');
        const select = document.querySelector(`.proxy-select[data-account-id="${accountId}"]`);
        const proxyId = select.value;
        const statusMessage = document.querySelector(`.proxy-status-message-${accountId}`);
        
        // Показываем индикатор загрузки
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        statusMessage.innerHTML = '<span class="text-info">Обновление...</span>';
        
        // Отправляем запрос на обновление прокси
        fetch('/accounts/updateProxy', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                accountId: accountId,
                proxyId: proxyId
            })
        })
        .then(response => response.json())
        .then(data => {
            // Восстанавливаем кнопку
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-save"></i>';
            
            // Показываем сообщение
            if (data.success) {
                statusMessage.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Прокси обновлен</span>';
                setTimeout(() => {
                    statusMessage.innerHTML = '';
                }, 3000);
            } else {
                statusMessage.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ' + data.message + '</span>';
            }
        })
        .catch(error => {
            // Восстанавливаем кнопку
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-save"></i>';
            
            // Показываем сообщение об ошибке
            statusMessage.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Ошибка при обновлении</span>';
            console.error('Error updating proxy:', error);
        });
    });
});
</script>
