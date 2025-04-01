<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Добавить новый прокси</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addProxyForm" aria-expanded="false">
                    <i class="fas fa-plus"></i> Добавить прокси
                </button>
            </div>
            <div class="card-body collapse" id="addProxyForm">
                <form action="/proxies/add" method="POST" class="ajax-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="ip" class="form-label">IP адрес</label>
                                <input type="text" class="form-control" id="ip" name="ip" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="port" class="form-label">Порт</label>
                                <input type="number" class="form-control" id="port" name="port" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="username" class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" id="username" name="username">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="protocol" class="form-label">Протокол</label>
                                <select class="form-select" id="protocol" name="protocol" required>
                                    <option value="http">HTTP</option>
                                    <option value="https">HTTPS</option>
                                    <option value="socks4">SOCKS4</option>
                                    <option value="socks5">SOCKS5</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="country" class="form-label">Страна</label>
                                <input type="text" class="form-control" id="country" name="country">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="mb-3 w-100 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Сохранить прокси
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Список прокси</h5>
            </div>
            <div class="card-body">
                <?php if (empty($proxies)): ?>
                <div class="alert alert-info">
                    Прокси не найдены. Добавьте новый прокси с помощью формы выше.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>IP:Порт</th>
                                <th>Протокол</th>
                                <th>Аутентификация</th>
                                <th>Страна</th>
                                <th>Статус</th>
                                <th>Последняя проверка</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proxies as $proxy): ?>
                            <tr class="proxy-list-item <?php 
                                if ($proxy['status'] === 'working') echo 'proxy-working';
                                elseif ($proxy['status'] === 'failed') echo 'proxy-failed';
                                else echo 'proxy-unchecked';
                            ?>">
                                <td><?php echo htmlspecialchars($proxy['ip'] . ':' . $proxy['port']); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($proxy['protocol'])); ?></td>
                                <td>
                                    <?php if (!empty($proxy['username']) && !empty($proxy['password'])): ?>
                                    <span class="badge bg-success">Есть</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Нет</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($proxy['country'] ?? 'Не указано'); ?></td>
                                <td id="proxy-status-<?php echo $proxy['id']; ?>">
                                    <?php
                                    switch ($proxy['status']) {
                                        case 'working':
                                            echo '<span class="badge bg-success">Работает</span>';
                                            break;
                                        case 'failed':
                                            echo '<span class="badge bg-danger">Не работает</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-warning">Не проверен</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($proxy['last_check']) {
                                        echo date('d.m.Y H:i', strtotime($proxy['last_check']));
                                    } else {
                                        echo 'Не проверялся';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-info check-proxy-btn" data-proxy-id="<?php echo $proxy['id']; ?>">
                                            <i class="fas fa-sync-alt"></i> Проверить
                                        </button>
                                        <button type="button" class="btn btn-sm <?php echo $proxy['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                onclick="window.location.href='/proxies/toggle/<?php echo $proxy['id']; ?>'">
                                            <?php if ($proxy['is_active']): ?>
                                            <i class="fas fa-times"></i> Деактивировать
                                            <?php else: ?>
                                            <i class="fas fa-check"></i> Активировать
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                data-delete-url="/proxies/delete/<?php echo $proxy['id']; ?>"
                                                data-item-name="прокси <?php echo htmlspecialchars($proxy['ip'] . ':' . $proxy['port']); ?>">
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
