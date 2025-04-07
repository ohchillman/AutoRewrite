<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Настройки генерации изображений</h5>
            </div>
            <div class="card-body">
                <form action="/image-settings/save" method="POST" class="ajax-form">
                    <div class="row">
                        <!-- Настройки API для генерации изображений -->
                        <div class="col-md-12 mb-4">
                            <h5>API для генерации изображений</h5>
                            <hr>
                            <div class="mb-3">
                                <label for="image_api_provider" class="form-label">API провайдер для изображений</label>
                                <select class="form-select" id="image_api_provider" name="settings[image_api_provider]">
                                    <option value="stable_diffusion" <?php echo ($settings['image_api_provider'] ?? '') == 'stable_diffusion' ? 'selected' : ''; ?>>Stable Diffusion</option>
                                    <option value="dalle" <?php echo ($settings['image_api_provider'] ?? '') == 'dalle' ? 'selected' : ''; ?>>DALL-E (OpenAI)</option>
                                    <option value="midjourney" <?php echo ($settings['image_api_provider'] ?? '') == 'midjourney' ? 'selected' : ''; ?>>Midjourney</option>
                                </select>
                                <div class="form-text">Выберите провайдера API для генерации изображений</div>
                            </div>
                            
                            <div class="mb-3 stable-diffusion-settings" <?php echo ($settings['image_api_provider'] ?? '') != 'stable_diffusion' ? 'style="display:none;"' : ''; ?>>
                                <label for="stable_diffusion_api_key" class="form-label">API ключ Stable Diffusion</label>
                                <input type="text" class="form-control" id="stable_diffusion_api_key" name="settings[stable_diffusion_api_key]" value="<?php echo htmlspecialchars($settings['stable_diffusion_api_key'] ?? ''); ?>">
                                <div class="form-text">Ключ API для доступа к Stable Diffusion API</div>
                            </div>
                            
                            <div class="mb-3 dalle-settings" <?php echo ($settings['image_api_provider'] ?? '') != 'dalle' ? 'style="display:none;"' : ''; ?>>
                                <label for="dalle_api_key" class="form-label">API ключ DALL-E (OpenAI)</label>
                                <input type="text" class="form-control" id="dalle_api_key" name="settings[dalle_api_key]" value="<?php echo htmlspecialchars($settings['dalle_api_key'] ?? ''); ?>">
                                <div class="form-text">Ключ API для доступа к DALL-E API от OpenAI</div>
                            </div>
                            
                            <div class="mb-3 midjourney-settings" <?php echo ($settings['image_api_provider'] ?? '') != 'midjourney' ? 'style="display:none;"' : ''; ?>>
                                <label for="midjourney_api_key" class="form-label">API ключ Midjourney</label>
                                <input type="text" class="form-control" id="midjourney_api_key" name="settings[midjourney_api_key]" value="<?php echo htmlspecialchars($settings['midjourney_api_key'] ?? ''); ?>">
                                <div class="form-text">Ключ API для доступа к Midjourney API</div>
                            </div>
                        </div>

                        <!-- Настройки генерации изображений -->
                        <div class="col-md-12 mb-4">
                            <h5>Параметры генерации</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image_generation_enabled" class="form-label">Автоматическая генерация изображений</label>
                                        <select class="form-select" id="image_generation_enabled" name="settings[image_generation_enabled]">
                                            <option value="0" <?php echo ($settings['image_generation_enabled'] ?? '0') == '0' ? 'selected' : ''; ?>>Отключено</option>
                                            <option value="1" <?php echo ($settings['image_generation_enabled'] ?? '0') == '1' ? 'selected' : ''; ?>>Включено</option>
                                        </select>
                                        <div class="form-text">Автоматически генерировать изображения для реврайтнутого контента</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image_count_per_post" class="form-label">Количество изображений для одного поста</label>
                                        <input type="number" class="form-control" id="image_count_per_post" name="settings[image_count_per_post]" min="1" max="5" value="<?php echo htmlspecialchars($settings['image_count_per_post'] ?? '1'); ?>">
                                        <div class="form-text">Сколько изображений генерировать для каждого поста</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="image_generation_template" class="form-label">Шаблон запроса для генерации изображений</label>
                                <textarea class="form-control" id="image_generation_template" name="settings[image_generation_template]" rows="3"><?php echo htmlspecialchars($settings['image_generation_template'] ?? 'Создай изображение для следующего текста: {content}'); ?></textarea>
                                <div class="form-text">Шаблон запроса для генерации изображений, где {content} будет заменен на заголовок или начало текста</div>
                            </div>
                        </div>

                        <!-- Настройки хранения изображений -->
                        <div class="col-md-12 mb-4">
                            <h5>Настройки хранения</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image_storage_provider" class="form-label">Провайдер хранения</label>
                                        <select class="form-select" id="image_storage_provider" name="settings[image_storage_provider]">
                                            <option value="local" <?php echo ($settings['image_storage_provider'] ?? 'local') == 'local' ? 'selected' : ''; ?>>Локальное хранилище</option>
                                            <option value="s3" <?php echo ($settings['image_storage_provider'] ?? 'local') == 's3' ? 'selected' : ''; ?>>Amazon S3</option>
                                        </select>
                                        <div class="form-text">Где хранить сгенерированные изображения</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image_format" class="form-label">Формат изображений</label>
                                        <select class="form-select" id="image_format" name="settings[image_format]">
                                            <option value="jpg" <?php echo ($settings['image_format'] ?? 'jpg') == 'jpg' ? 'selected' : ''; ?>>JPG</option>
                                            <option value="png" <?php echo ($settings['image_format'] ?? 'jpg') == 'png' ? 'selected' : ''; ?>>PNG</option>
                                            <option value="webp" <?php echo ($settings['image_format'] ?? 'jpg') == 'webp' ? 'selected' : ''; ?>>WebP</option>
                                        </select>
                                        <div class="form-text">Формат для сохранения сгенерированных изображений</div>
                                    </div>
                                </div>
                            </div>
                        
                            
                            <div class="local-storage-settings" <?php echo ($settings['image_storage_provider'] ?? 'local') != 'local' ? 'style="display:none;"' : ''; ?>>
                                <div class="mb-3">
                                    <label for="local_storage_path" class="form-label">Путь для сохранения</label>
                                    <input type="text" class="form-control" id="local_storage_path" name="settings[local_storage_path]" value="<?php echo htmlspecialchars($settings['local_storage_path'] ?? '/var/www/html/uploads/images'); ?>">
                                    <div class="form-text">Локальный путь для сохранения изображений</div>
                                </div>
                            </div>

                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Управление временными изображениями</h5>
                                </div>
                                <div class="card-body">
                                    <p>Временные изображения сохраняются в директории <code>/uploads/temp/</code> и могут занимать много места.</p>
                                    <button type="button" id="clearTempImagesBtn" class="btn btn-warning">
                                        <i class="fas fa-trash"></i> Очистить папку с временными изображениями
                                    </button>
                                </div>
                            </div>
                            
                            <div class="s3-storage-settings" <?php echo ($settings['image_storage_provider'] ?? 'local') != 's3' ? 'style="display:none;"' : ''; ?>>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="s3_access_key" class="form-label">Access Key</label>
                                            <input type="text" class="form-control" id="s3_access_key" name="settings[s3_access_key]" value="<?php echo htmlspecialchars($settings['s3_access_key'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="s3_secret_key" class="form-label">Secret Key</label>
                                            <input type="text" class="form-control" id="s3_secret_key" name="settings[s3_secret_key]" value="<?php echo htmlspecialchars($settings['s3_secret_key'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="s3_bucket" class="form-label">Bucket</label>
                                            <input type="text" class="form-control" id="s3_bucket" name="settings[s3_bucket]" value="<?php echo htmlspecialchars($settings['s3_bucket'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="s3_region" class="form-label">Region</label>
                                            <input type="text" class="form-control" id="s3_region" name="settings[s3_region]" value="<?php echo htmlspecialchars($settings['s3_region'] ?? 'us-east-1'); ?>">
                                        </div>
                                    </div>
                                </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Показ/скрытие полей в зависимости от выбранного провайдера API
    const imageApiProviderSelect = document.getElementById('image_api_provider');
    const stableDiffusionSettings = document.querySelector('.stable-diffusion-settings');
    const dalleSettings = document.querySelector('.dalle-settings');
    const midjourneySettings = document.querySelector('.midjourney-settings');
    const clearTempImagesBtn = document.getElementById('clearTempImagesBtn');

    if (imageApiProviderSelect) {
        imageApiProviderSelect.addEventListener('change', function() {
            stableDiffusionSettings.style.display = 'none';
            dalleSettings.style.display = 'none';
            midjourneySettings.style.display = 'none';
            
            if (this.value === 'stable_diffusion') {
                stableDiffusionSettings.style.display = 'block';
            } else if (this.value === 'dalle') {
                dalleSettings.style.display = 'block';
            } else if (this.value === 'midjourney') {
                midjourneySettings.style.display = 'block';
            }
        });
    }
    
    // Показ/скрытие полей в зависимости от выбранного провайдера хранилища
    const storageProviderSelect = document.getElementById('image_storage_provider');
    const localStorageSettings = document.querySelector('.local-storage-settings');
    const s3StorageSettings = document.querySelector('.s3-storage-settings');
    
    if (storageProviderSelect) {
        storageProviderSelect.addEventListener('change', function() {
            localStorageSettings.style.display = 'none';
            s3StorageSettings.style.display = 'none';
            
            if (this.value === 'local') {
                localStorageSettings.style.display = 'block';
            } else if (this.value === 's3') {
                s3StorageSettings.style.display = 'block';
            }
        });
    }

    if (clearTempImagesBtn) {
        clearTempImagesBtn.addEventListener('click', function() {
            if (confirm('Вы уверены, что хотите удалить все временные изображения?')) {
                // Отключаем кнопку и показываем индикатор загрузки
                clearTempImagesBtn.disabled = true;
                clearTempImagesBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Очистка...';
                
                // Отправляем запрос на очистку
                fetch('/image-settings/clearTemp', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Показываем сообщение
                    showNotification(data.message, data.success ? 'success' : 'danger');
                    
                    // Восстанавливаем кнопку
                    clearTempImagesBtn.disabled = false;
                    clearTempImagesBtn.innerHTML = '<i class="fas fa-trash"></i> Очистить папку с временными изображениями';
                })
                .catch(error => {
                    // Показываем сообщение об ошибке
                    showNotification('Ошибка при очистке папки: ' + error.message, 'danger');
                    
                    // Восстанавливаем кнопку
                    clearTempImagesBtn.disabled = false;
                    clearTempImagesBtn.innerHTML = '<i class="fas fa-trash"></i> Очистить папку с временными изображениями';
                });
            }
        });
    }
    
    // Функция для отображения уведомлений
    function showNotification(message, type) {
        // Создаем элемент уведомления
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Находим контейнер
        const container = document.querySelector('.container') || document.body;
        
        // Вставляем уведомление в начало контейнера
        container.insertBefore(alertDiv, container.firstChild);
        
        // Автоматически удаляем уведомление через 5 секунд
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                alertDiv.remove();
            }, 300);
        }, 5000);
    }
});

</script>
