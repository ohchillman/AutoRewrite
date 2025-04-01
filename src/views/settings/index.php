<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Общие настройки API</h5>
            </div>
            <div class="card-body">
                <form action="/settings/save" method="POST" class="ajax-form">
                    <div class="row">
                        <!-- Make.com API настройки -->
                        <div class="col-md-12 mb-4">
                            <h5>Make.com API</h5>
                            <hr>
                            <div class="mb-3">
                                <label for="makecom_api_key" class="form-label">API ключ</label>
                                <input type="text" class="form-control" id="makecom_api_key" name="settings[makecom_api_key]" value="<?php echo htmlspecialchars($settings['makecom_api_key'] ?? ''); ?>">
                                <div class="form-text">Ключ API для доступа к Make.com</div>
                            </div>
                            <div class="mb-3">
                                <label for="makecom_api_url" class="form-label">URL вебхука</label>
                                <input type="text" class="form-control" id="makecom_api_url" name="settings[makecom_api_url]" value="<?php echo htmlspecialchars($settings['makecom_api_url'] ?? ''); ?>">
                                <div class="form-text">URL вебхука для отправки запросов на реврайт</div>
                            </div>
                        </div>

                        <!-- Настройки реврайта -->
                        <div class="col-md-12 mb-4">
                            <h5>Настройки реврайта</h5>
                            <hr>
                            <div class="mb-3">
                                <label for="rewrite_template" class="form-label">Шаблон запроса для реврайта</label>
                                <textarea class="form-control" id="rewrite_template" name="settings[rewrite_template]" rows="3"><?php echo htmlspecialchars($settings['rewrite_template'] ?? 'Перепиши следующий текст, сохраняя смысл, но изменяя формулировки: {content}'); ?></textarea>
                                <div class="form-text">Шаблон запроса для реврайта, где {content} будет заменен на оригинальный текст</div>
                            </div>
                        </div>

                        <!-- Настройки производительности -->
                        <div class="col-md-12 mb-4">
                            <h5>Настройки производительности</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="max_parsing_threads" class="form-label">Максимальное количество потоков парсинга</label>
                                        <input type="number" class="form-control" id="max_parsing_threads" name="settings[max_parsing_threads]" min="1" max="10" value="<?php echo htmlspecialchars($settings['max_parsing_threads'] ?? '3'); ?>">
                                        <div class="form-text">Количество одновременных процессов парсинга</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="max_rewrite_threads" class="form-label">Максимальное количество потоков реврайта</label>
                                        <input type="number" class="form-control" id="max_rewrite_threads" name="settings[max_rewrite_threads]" min="1" max="10" value="<?php echo htmlspecialchars($settings['max_rewrite_threads'] ?? '2'); ?>">
                                        <div class="form-text">Количество одновременных процессов реврайта</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="max_posting_threads" class="form-label">Максимальное количество потоков постинга</label>
                                        <input type="number" class="form-control" id="max_posting_threads" name="settings[max_posting_threads]" min="1" max="10" value="<?php echo htmlspecialchars($settings['max_posting_threads'] ?? '5'); ?>">
                                        <div class="form-text">Количество одновременных процессов постинга</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Дополнительные настройки -->
                        <div class="col-md-12 mb-4">
                            <h5>Дополнительные настройки</h5>
                            <hr>
                            <div class="mb-3">
                                <label for="min_content_length" class="form-label">Минимальная длина контента для реврайта</label>
                                <input type="number" class="form-control" id="min_content_length" name="settings[min_content_length]" min="10" value="<?php echo htmlspecialchars($settings['min_content_length'] ?? '100'); ?>">
                                <div class="form-text">Минимальное количество символов для обработки контента</div>
                            </div>
                            <div class="mb-3">
                                <label for="auto_posting" class="form-label">Автоматический постинг</label>
                                <select class="form-select" id="auto_posting" name="settings[auto_posting]">
                                    <option value="0" <?php echo ($settings['auto_posting'] ?? '0') == '0' ? 'selected' : ''; ?>>Отключено</option>
                                    <option value="1" <?php echo ($settings['auto_posting'] ?? '0') == '1' ? 'selected' : ''; ?>>Включено</option>
                                </select>
                                <div class="form-text">Автоматически публиковать реврайтнутый контент в аккаунты</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить настройки
                        </button>
                    </div>
                </form>
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
