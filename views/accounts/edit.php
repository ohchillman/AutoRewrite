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
                                <label for="proxy_id" class="form-label">Прокси (опционально)</label>
                                <select class="form-select" id="proxy_id" name="proxy_id">
                                    <option value="">Без прокси</option>
                                    <?php foreach ($proxies as $proxy): ?>
                                    <option value="<?php echo $proxy['id']; ?>" <?php echo ($account['proxy_id'] == $proxy['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($proxy['ip'] . ':' . $proxy['port']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
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

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>