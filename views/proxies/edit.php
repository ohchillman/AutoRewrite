<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Редактирование прокси</h5>
                <a href="/proxies" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к списку
                </a>
            </div>
            <div class="card-body">
                <form action="/proxies/edit/<?php echo $proxy['id']; ?>" method="POST" class="ajax-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">Название прокси</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($proxy['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="ip" class="form-label">IP адрес</label>
                                <input type="text" class="form-control" id="ip" name="ip" value="<?php echo htmlspecialchars($proxy['ip']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="port" class="form-label">Порт</label>
                                <input type="number" class="form-control" id="port" name="port" value="<?php echo htmlspecialchars($proxy['port']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="protocol" class="form-label">Протокол</label>
                                <select class="form-select" id="protocol" name="protocol" required>
                                    <option value="http" <?php echo $proxy['protocol'] == 'http' ? 'selected' : ''; ?>>HTTP</option>
                                    <option value="https" <?php echo $proxy['protocol'] == 'https' ? 'selected' : ''; ?>>HTTPS</option>
                                    <option value="socks4" <?php echo $proxy['protocol'] == 'socks4' ? 'selected' : ''; ?>>SOCKS4</option>
                                    <option value="socks5" <?php echo $proxy['protocol'] == 'socks5' ? 'selected' : ''; ?>>SOCKS5</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="username" class="form-label">Имя пользователя</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($proxy['username'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Оставьте пустым, чтобы не менять">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="country" class="form-label">Страна</label>
                                <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($proxy['country'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="ip_change_url" class="form-label">URL для смены IP</label>
                                <input type="text" class="form-control" id="ip_change_url" name="ip_change_url" value="<?php echo htmlspecialchars($proxy['ip_change_url'] ?? ''); ?>" placeholder="http://...">
                                <div class="form-text">Необязательное поле. URL для запроса смены IP.</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить изменения
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>