<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Добавить новый источник</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addSourceForm" aria-expanded="false">
                    <i class="fas fa-plus"></i> Добавить источник
                </button>
            </div>
            <div class="card-body collapse" id="addSourceForm">
                <form action="/parsing/add" method="POST" class="ajax-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Название источника</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="form-text">Название для идентификации источника</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="url" class="form-label">URL источника</label>
                                <input type="text" class="form-control" id="url" name="url" required>
                                <div class="form-text">URL для парсинга контента</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="source_type" class="form-label">Тип источника</label>
                                <select class="form-select" id="source_type" name="source_type" required>
                                    <option value="">Выберите тип источника</option>
                                    <option value="twitter">Twitter</option>
                                    <option value="linkedin">LinkedIn</option>
                                    <option value="youtube">YouTube</option>
                                    <option value="blog">Блог</option>
                                    <option value="rss">RSS-лента</option>
                                    <option value="other">Другое</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="parsing_frequency" class="form-label">Частота парсинга (минуты)</label>
                                <input type="number" class="form-control" id="parsing_frequency" name="parsing_frequency" min="5" value="60">
                                <div class="form-text">Как часто проверять источник на новый контент</div>
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
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="mb-3 w-100 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Сохранить источник
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row source-fields twitter-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для Twitter</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="twitter_username" class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" id="twitter_username" name="additional_settings[username]">
                                <div class="form-text">Имя пользователя без @</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="twitter_count" class="form-label">Количество твитов</label>
                                <input type="number" class="form-control" id="twitter_count" name="additional_settings[count]" min="1" max="100" value="20">
                                <div class="form-text">Количество твитов для получения</div>
                            </div>
                        </div>
                    </div>

                    <div class="row source-fields linkedin-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для LinkedIn</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="linkedin_username" class="form-label">Имя пользователя/компании</label>
                                <input type="text" class="form-control" id="linkedin_username" name="additional_settings[username]">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="linkedin_type" class="form-label">Тип профиля</label>
                                <select class="form-select" id="linkedin_type" name="additional_settings[type]">
                                    <option value="person">Личный профиль</option>
                                    <option value="company">Компания</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row source-fields youtube-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для YouTube</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="youtube_channel" class="form-label">ID канала</label>
                                <input type="text" class="form-control" id="youtube_channel" name="additional_settings[channel_id]">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="youtube_count" class="form-label">Количество видео</label>
                                <input type="number" class="form-control" id="youtube_count" name="additional_settings[count]" min="1" max="50" value="10">
                                <div class="form-text">Количество видео для получения</div>
                            </div>
                        </div>
                    </div>

                    <div class="row source-fields rss-fields d-none">
                        <div class="col-md-12">
                            <h6 class="mb-3">Дополнительные настройки для RSS</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rss_items" class="form-label">Количество записей</label>
                                <input type="number" class="form-control" id="rss_items" name="additional_settings[items]" min="1" max="100" value="20">
                                <div class="form-text">Количество записей для получения</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rss_full_content" class="form-label">Получать полный контент</label>
                                <select class="form-select" id="rss_full_content" name="additional_settings[full_content]">
                                    <option value="1">Да</option>
                                    <option value="0">Нет</option>
                                </select>
                                <div class="form-text">Пытаться получить полный контент статьи</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Список источников</h5>
            </div>
            <div class="card-body">
                <?php if (empty($sources)): ?>
                <div class="alert alert-info">
                    Источники не найдены. Добавьте новый источник с помощью формы выше.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>URL</th>
                                <th>Тип</th>
                                <th>Частота</th>
                                <th>Прокси</th>
                                <th>Последний парсинг</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sources as $source): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($source['name']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($source['url']); ?>" target="_blank">
                                        <?php echo htmlspecialchars(substr($source['url'], 0, 30) . (strlen($source['url']) > 30 ? '...' : '')); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    switch ($source['source_type']) {
                                        case 'twitter':
                                            echo '<span class="badge bg-info">Twitter</span>';
                                            break;
                                        case 'linkedin':
                                            echo '<span class="badge bg-primary">LinkedIn</span>';
                                            break;
                                        case 'youtube':
                                            echo '<span class="badge bg-danger">YouTube</span>';
                                            break;
                                        case 'blog':
                                            echo '<span class="badge bg-success">Блог</span>';
                                            break;
                                        case 'rss':
                                            echo '<span class="badge bg-warning">RSS</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Другое</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $source['parsing_frequency']; ?> мин</td>
                                <td>
                                    <?php if (!empty($source['proxy_ip'])): ?>
                                    <?php echo htmlspecialchars($source['proxy_ip'] . ':' . $source['proxy_port']); ?>
                                    <?php else: ?>
                                    <span class="text-muted">Нет</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($source['last_parsed']) {
                                        echo date('d.m.Y H:i', strtotime($source['last_parsed']));
                                    } else {
                                        echo '<span class="text-muted">Не парсился</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($source['is_active']): ?>
                                    <span class="badge bg-success">Активен</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Неактивен</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="/parsing/edit/<?php echo $source['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i> Редактировать
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="window.location.href='/parsing/parse/<?php echo $source['id']; ?>'">
                                            <i class="fas fa-sync-alt"></i> Парсить
                                        </button>
                                        <button type="button" class="btn btn-sm <?php echo $source['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                onclick="window.location.href='/parsing/toggle/<?php echo $source['id']; ?>'">
                                            <?php if ($source['is_active']): ?>
                                            <i class="fas fa-times"></i> Деактивировать
                                            <?php else: ?>
                                            <i class="fas fa-check"></i> Активировать
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                data-delete-url="/parsing/delete/<?php echo $source['id']; ?>"
                                                data-item-name="источник <?php echo htmlspecialchars($source['name']); ?>">
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
            
            if (sourceType === 'twitter') {
                document.querySelector('.twitter-fields').classList.remove('d-none');
            } else if (sourceType === 'linkedin') {
                document.querySelector('.linkedin-fields').classList.remove('d-none');
            } else if (sourceType === 'youtube') {
                document.querySelector('.youtube-fields').classList.remove('d-none');
            } else if (sourceType === 'rss') {
                document.querySelector('.rss-fields').classList.remove('d-none');
            }
        });
    }
});
</script>
