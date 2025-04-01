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
            <div class="card-header">
                <h5 class="mb-0">Список аккаунтов</h5>
            </div>
            <div class="card-body">
                <?php if (empty($accounts)): ?>
                <div class="alert alert-info">
                    Аккаунты не найдены. Добавьте новый аккаунт с помощью формы выше.
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($accounts as $account): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card account-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo htmlspecialchars($account['name']); ?></h6>
                                <span class="badge <?php echo $account['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $account['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Тип:</strong> 
                                    <?php echo ucfirst(htmlspecialchars($account['account_type_name'])); ?>
                                </div>
                                
                                <?php if (!empty($account['username'])): ?>
                                <div class="mb-3">
                                    <strong>Логин:</strong> 
                                    <?php echo htmlspecialchars($account['username']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($account['proxy_ip'])): ?>
                                <div class="mb-3">
                                    <strong>Прокси:</strong> 
                                    <?php echo htmlspecialchars($account['proxy_ip'] . ':' . $account['proxy_port']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <strong>Последнее использование:</strong> 
                                    <?php echo $account['last_used'] ? date('d.m.Y H:i', strtotime($account['last_used'])) : 'Не использовался'; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100" role="group">
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
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
