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
                                <label for="proxy_id" class="form-label">Прокси (опционально)</label>
                                <select class="form-select" id="proxy_id" name="proxy_id">
                                    <option value="">Без прокси</option>
                                    <?php foreach ($proxies as $proxy): ?>
                                    <option value="<?php echo $proxy['id']; ?>"><?php echo htmlspecialchars($proxy['ip'] . ':' . $proxy['port']); ?></option>
                                    <?php endforeach; ?>
                                </select>
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

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
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
                <div class="bulk-actions-accounts" style="display: none;">
                    <button type="button" class="btn btn-danger btn-sm delete-selected-accounts">
                        <i class="fas fa-trash"></i> Удалить выбранное
                    </button>
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
                                <td><?php echo !empty($account['proxy_ip']) ? htmlspecialchars($account['proxy_ip'] . ':' . $account['proxy_port']) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $account['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $account['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
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
// Показывать/скрывать поля в зависимости от типа аккаунта
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
    
    // Обработка выбора всех аккаунтов
    const selectAllAccounts = document.getElementById('selectAllAccounts');
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
});
</script>
