<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Редактирование источника</h5>
                <a href="/parsing" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к списку
                </a>
            </div>
            <div class="card-body">
                <form action="/parsing/edit/<?php echo $source['id']; ?>" method="POST" class="ajax-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Название источника</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($source['name']); ?>" required>
                                <div class="form-text">Название для идентификации источника</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="url" class="form-label">URL источника</label>
                                <input type="text" class="form-control" id="url" name="url" value="<?php echo htmlspecialchars($source['url']); ?>" required>
                                <div class="form-text">URL для парсинга контента</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="source_type" class="form-label">Тип источника</label>
                                <select class="form-select" id="source_type" name="source_type" required>
                                    <option value="">Выберите тип источника</option>
                                    <option value="rss" <?php echo $source['source_type'] == 'rss' ? 'selected' : ''; ?>>RSS-лента</option>
                                    <option value="blog" <?php echo $source['source_type'] == 'blog' ? 'selected' : ''; ?>>Новостной сайт</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="parsing_frequency" class="form-label">Частота парсинга (минуты)</label>
                                <input type="number" class="form-control" id="parsing_frequency" name="parsing_frequency" min="5" value="<?php echo $source['parsing_frequency']; ?>">
                                <div class="form-text">Как часто проверять источник на новый контент</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="proxy_id" class="form-label">Прокси (опционально)</label>
                                <select class="form-select" id="proxy_id" name="proxy_id">
                                    <option value="">Без прокси</option>
                                    <?php foreach ($proxies as $proxy): ?>
                                    <option value="<?php echo $proxy['id']; ?>" <?php echo ($source['proxy_id'] == $proxy['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($proxy['ip'] . ':' . $proxy['port']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="mb-3 w-100 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Сохранить изменения
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Декодируем дополнительные настройки
                    $additionalSettings = !empty($source['additional_settings']) ? 
                        json_decode($source['additional_settings'], true) : [];
                    
                    // Получаем значения настроек с значениями по умолчанию
                    $items = $additionalSettings['items'] ?? 20;
                    $fullContent = isset($additionalSettings['full_content']) ? 
                        (bool)$additionalSettings['full_content'] : false;
                    $selectors = $additionalSettings['selectors'] ?? [];
                    ?>
                    
                    <!-- Дополнительные настройки для RSS -->
                    <div class="row source-fields rss-fields <?php echo $source['source_type'] == 'rss' ? '' : 'd-none'; ?>">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для RSS</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rss_items" class="form-label">Количество записей</label>
                                <input type="number" class="form-control" id="rss_items" name="additional_settings[items]" min="1" max="100" value="<?php echo $items; ?>">
                                <div class="form-text">Количество записей для получения</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rss_full_content" class="form-label">Получать полный контент</label>
                                <select class="form-select" id="rss_full_content" name="additional_settings[full_content]">
                                    <option value="1" <?php echo $fullContent ? 'selected' : ''; ?>>Да</option>
                                    <option value="0" <?php echo !$fullContent ? 'selected' : ''; ?>>Нет</option>
                                </select>
                                <div class="form-text">Пытаться получить полный контент статьи (может занять больше времени)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Дополнительные настройки для новостного сайта -->
                    <div class="row source-fields blog-fields <?php echo $source['source_type'] == 'blog' ? '' : 'd-none'; ?>">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для новостного сайта</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="blog_items" class="form-label">Количество записей</label>
                                <input type="number" class="form-control" id="blog_items" name="additional_settings[items]" min="1" max="50" value="<?php echo $items; ?>">
                                <div class="form-text">Количество записей для получения</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="blog_full_content" class="form-label">Получать полный контент</label>
                                <select class="form-select" id="blog_full_content" name="additional_settings[full_content]">
                                    <option value="1" <?php echo $fullContent ? 'selected' : ''; ?>>Да</option>
                                    <option value="0" <?php echo !$fullContent ? 'selected' : ''; ?>>Нет</option>
                                </select>
                                <div class="form-text">Пытаться получить полный контент статьи (может занять больше времени)</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="selectors_container" class="form-label">Селекторы для парсинга (опционально)</label>
                                <input type="text" class="form-control mb-2" id="selectors_container" name="additional_settings[selectors][container]" placeholder="XPath селектор для контейнеров статей, например: //article" value="<?php echo htmlspecialchars($selectors['container'] ?? ''); ?>">
                                <input type="text" class="form-control mb-2" id="selectors_title" name="additional_settings[selectors][title]" placeholder="XPath селектор для заголовка, например: .//h1 | .//h2" value="<?php echo htmlspecialchars($selectors['title'] ?? ''); ?>">
                                <input type="text" class="form-control mb-2" id="selectors_content" name="additional_settings[selectors][content]" placeholder="XPath селектор для контента, например: .//div[contains(@class, 'content')]" value="<?php echo htmlspecialchars($selectors['content'] ?? ''); ?>">
                                <input type="text" class="form-control mb-2" id="selectors_link" name="additional_settings[selectors][link]" placeholder="XPath селектор для ссылки, например: .//a[contains(@class, 'read-more')] | .//h2/a" value="<?php echo htmlspecialchars($selectors['link'] ?? ''); ?>">
                                <input type="text" class="form-control" id="selectors_date" name="additional_settings[selectors][date]" placeholder="XPath селектор для даты, например: .//time | .//span[contains(@class, 'date')]" value="<?php echo htmlspecialchars($selectors['date'] ?? ''); ?>">
                                <div class="form-text">Оставьте пустыми для автоматического определения</div>
                            </div>
                        </div>
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
// Показывать/скрывать поля в зависимости от типа источника
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>