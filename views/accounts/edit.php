<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Редактирование аккаунта</h5>
                <a href="/accounts" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к списку
                </a>
            </div>
            <div class="card-body">
                <form action="/accounts/edit/<?php echo $account['id']; ?>" method="POST" class="ajax-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Название аккаунта</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($account['name']); ?>" required>
                                <div class="form-text">Название для идентификации аккаунта</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="username" class="form-label">Имя пользователя / Email</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($account['username'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="proxy_id" class="form-label">Прокси</label>
                                <div class="input-group">
                                    <select class="form-select" id="proxy_id" name="proxy_id">
                                        <option value="">Без прокси</option>
                                        <?php foreach ($proxies as $proxy): ?>
                                        <option value="<?php echo $proxy['id']; ?>" <?php echo ($account['proxy_id'] == $proxy['id']) ? 'selected' : ''; ?> 
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
                                    <button class="btn btn-outline-secondary" type="button" id="testProxyBtn" <?php echo empty($account['proxy_id']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-sync-alt"></i> Проверить
                                    </button>
                                </div>
                                <div id="proxyStatus" class="form-text mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Дополнительные поля в зависимости от типа аккаунта
                    switch(strtolower($account['account_type_name'])):
                        case 'twitter':
                    ?>
                    <div class="row account-fields">
                        <div class="col-md-12">
                            <h6 class="mb-3">Данные для Twitter</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="api_key" class="form-label">API Key</label>
                                <input type="text" class="form-control" id="api_key" name="api_key" value="<?php echo htmlspecialchars($account['api_key'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="api_secret" class="form-label">API Secret</label>
                                <input type="text" class="form-control" id="api_secret" name="api_secret" value="<?php echo htmlspecialchars($account['api_secret'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="access_token" class="form-label">Access Token</label>
                                <input type="text" class="form-control" id="access_token" name="access_token" value="<?php echo htmlspecialchars($account['access_token'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="access_token_secret" class="form-label">Access Token Secret</label>
                                <input type="text" class="form-control" id="access_token_secret" name="access_token_secret" value="<?php echo htmlspecialchars($account['access_token_secret'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <?php
                        break;
                        case 'linkedin':
                    ?>
                    <div class="row account-fields">
                        <div class="col-md-12">
                            <h6 class="mb-3">Данные для LinkedIn</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Оставьте пустым, чтобы не менять">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="access_token" class="form-label">Access Token</label>
                                <input type="text" class="form-control" id="access_token" name="access_token" value="<?php echo htmlspecialchars($account['access_token'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="refresh_token" class="form-label">Refresh Token</label>
                                <input type="text" class="form-control" id="refresh_token" name="refresh_token" value="<?php echo htmlspecialchars($account['refresh_token'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <?php
                        break;
                        // Добавьте кейсы для других типов аккаунтов при необходимости
                        default:
                    ?>
                    <div class="row account-fields">
                        <div class="col-md-12">
                            <h6 class="mb-3">Данные для <?php echo htmlspecialchars($account['account_type_name']); ?></h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Оставьте пустым, чтобы не менять">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="additional_data" class="form-label">Дополнительные данные</label>
                                <textarea class="form-control" id="additional_data" name="additional_data" rows="3"><?php echo htmlspecialchars($account['additional_data'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endswitch; ?>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Проверка аккаунта</h6>
                                    <p class="card-text">Проверьте работоспособность аккаунта с текущими настройками и прокси</p>
                                    <button type="button" id="verifyAccountBtn" class="btn btn-info">
                                        <i class="fas fa-check-circle"></i> Проверить аккаунт
                                    </button>
                                    <div id="accountVerificationStatus" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка изменения выбора прокси
    const proxySelect = document.getElementById('proxy_id');
    const testProxyBtn = document.getElementById('testProxyBtn');
    const proxyStatus = document.getElementById('proxyStatus');
    
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
    
    // Обработчик кнопки проверки аккаунта
    const verifyAccountBtn = document.getElementById('verifyAccountBtn');
    const accountVerificationStatus = document.getElementById('accountVerificationStatus');
    
    verifyAccountBtn.addEventListener('click', function() {
        // Собираем данные формы
        const formData = new FormData(document.querySelector('.ajax-form'));
        const accountId = <?php echo $account['id']; ?>;
        
        // Показываем индикатор загрузки
        accountVerificationStatus.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Проверка аккаунта...</div>';
        verifyAccountBtn.disabled = true;
        
        // Отправляем запрос на проверку аккаунта
        fetch('/accounts/verify/' + accountId, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                accountVerificationStatus.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            } else {
                accountVerificationStatus.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + data.message + '</div>';
            }
            verifyAccountBtn.disabled = false;
        })
        .catch(error => {
            accountVerificationStatus.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Ошибка при проверке аккаунта</div>';
            verifyAccountBtn.disabled = false;
            console.error('Account verification error:', error);
        });
    });
});
</script>
