<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Реврайтнутый контент</h5>
                <div>
                    <a href="/rewrite" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад к списку
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Оригинал</h5>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo htmlspecialchars($content['original_title']); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <?php echo nl2br(htmlspecialchars($content['original_content'])); ?>
                                </div>
                                <div class="small text-muted">
                                    <div><strong>Источник:</strong> <?php echo htmlspecialchars($content['source_name']); ?></div>
                                    <div><strong>Автор:</strong> <?php echo htmlspecialchars($content['original_author']); ?></div>
                                    <div><strong>Дата публикации:</strong> <?php echo date('d.m.Y H:i', strtotime($content['original_date'])); ?></div>
                                    <div>
                                        <strong>URL:</strong> 
                                        <a href="<?php echo htmlspecialchars($content['original_url']); ?>" target="_blank">
                                            <?php echo htmlspecialchars(substr($content['original_url'], 0, 50) . (strlen($content['original_url']) > 50 ? '...' : '')); ?>
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>Реврайт</h5>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo htmlspecialchars($content['title']); ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <?php echo nl2br(htmlspecialchars($content['content'])); ?>
                                </div>
                                <div class="small text-muted">
                                    <div><strong>Дата реврайта:</strong> <?php echo date('d.m.Y H:i', strtotime($content['rewrite_date'])); ?></div>
                                    <div>
                                        <strong>Статус:</strong> 
                                        <?php
                                        switch ($content['status']) {
                                            case 'rewritten':
                                                echo '<span class="badge bg-success">Реврайтнут</span>';
                                                break;
                                            case 'posted':
                                                echo '<span class="badge bg-primary">Опубликован</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">Ожидает</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Публикация контента</h5>
            </div>
            <div class="card-body">
                <?php if (empty($accounts)): ?>
                <div class="alert alert-warning">
                    Нет активных аккаунтов для публикации. Добавьте аккаунты в разделе "Аккаунты".
                </div>
                <?php else: ?>
                <form action="/rewrite/post" method="POST" class="ajax-form">
                    <input type="hidden" name="rewritten_id" value="<?php echo $content['id']; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="account_id" class="form-label">Выберите аккаунт для публикации</label>
                                <select class="form-select" id="account_id" name="account_id" required>
                                    <option value="">Выберите аккаунт</option>
                                    <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars($account['name'] . ' (' . $account['account_type_name'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="mb-3 w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Опубликовать
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">История публикаций</h5>
            </div>
            <div class="card-body">
                <?php if (empty($posts)): ?>
                <div class="alert alert-info">
                    Этот контент еще не был опубликован ни в один аккаунт.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Аккаунт</th>
                                <th>Тип</th>
                                <th>Дата публикации</th>
                                <th>Статус</th>
                                <th>Ссылка</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['account_name']); ?></td>
                                <td>
                                    <?php
                                    switch ($post['account_type_name']) {
                                        case 'Twitter':
                                            echo '<span class="badge bg-info">Twitter</span>';
                                            break;
                                        case 'LinkedIn':
                                            echo '<span class="badge bg-primary">LinkedIn</span>';
                                            break;
                                        case 'YouTube':
                                            echo '<span class="badge bg-danger">YouTube</span>';
                                            break;
                                        case 'Threads':
                                            echo '<span class="badge bg-success">Threads</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">' . htmlspecialchars($post['account_type_name']) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($post['posted_at'])); ?></td>
                                <td>
                                    <?php
                                    switch ($post['status']) {
                                        case 'posted':
                                            echo '<span class="badge bg-success">Опубликован</span>';
                                            break;
                                        case 'failed':
                                            echo '<span class="badge bg-danger">Ошибка</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="badge bg-warning">В очереди</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Неизвестно</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($post['post_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($post['post_url']); ?>" target="_blank">
                                        Просмотреть <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">Недоступно</span>
                                    <?php endif; ?>
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
