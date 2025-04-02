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
                                    <option value="twitter" <?php echo $source['source_type'] == 'twitter' ? 'selected' : ''; ?>>Twitter</option>
                                    <option value="linkedin" <?php echo $source['source_type'] == 'linkedin' ? 'selected' : ''; ?>>LinkedIn</option>
                                    <option value="youtube" <?php echo $source['source_type'] == 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                                    <option value="blog" <?php echo $source['source_type'] == 'blog' ? 'selected' : ''; ?>>Блог</option>
                                    <option value="rss" <?php echo $source['source_type'] == 'rss' ? 'selected' : ''; ?>>RSS-лента</option>
                                    <option value="other" <?php echo $source['source_type'] == 'other' ? 'selected' : ''; ?>>Другое</option>
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
                                    <i class="fas fa-save"></i> Сохранить источник
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if ($source['source_type'] == 'twitter'): ?>
                    <div class="row source-fields">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для Twitter</h6>
                        </div>
                        <?php 
                        $additionalData = !empty($source['additional_settings']) ? json_decode($source['additional_settings'], true) : [];
                        ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="twitter_username" class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" id="twitter_username" name="additional_settings[username]" value="<?php echo htmlspecialchars($additionalData['username'] ?? ''); ?>">
                                <div class="form-text">Имя пользователя без @</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="twitter_count" class="form-label">Количество твитов</label>
                                <input type="number" class="form-control" id="twitter_count" name="additional_settings[count]" min="1" max="100" value="<?php echo htmlspecialchars($additionalData['count'] ?? '20'); ?>">
                                <div class="form-text">Количество твитов для получения</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($source['source_type'] == 'rss'): ?>
                    <div class="row source-fields">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для RSS</h6>
                        </div>
                        <?php 
                        $additionalData = !empty($source['additional_settings']) ? json_decode($source['additional_settings'], true) : [];
                        ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rss_items" class="form-label">Количество записей</label>
                                <input type="number" class="form-control" id="rss_items" name="additional_settings[items]" min="1" max="100" value="<?php echo htmlspecialchars($additionalData['items'] ?? '20'); ?>">
                                <div class="form-text">Количество записей для получения</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rss_full_content" class="form-label">Получать полный контент</label>
                                <select class="form-select" id="rss_full_content" name="additional_settings[full_content]">
                                    <option value="1" <?php echo isset($additionalData['full_content']) && $additionalData['full_content'] == 1 ? 'selected' : ''; ?>>Да</option>
                                    <option value="0" <?php echo isset($additionalData['full_content']) && $additionalData['full_content'] == 0 ? 'selected' : ''; ?>>Нет</option>
                                </select>
                                <div class="form-text">Пытаться получить полный контент статьи</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Аналогично добавьте дополнительные поля для других типов источников -->
                </form>
            </div>
        </div>
    </div>
</div>