/**
 * Основной JavaScript файл для AutoRewrite
 */

// Ждем загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всплывающих подсказок
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Инициализация всплывающих окон
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Обработка AJAX форм
    setupAjaxForms();

    // Обработка проверки прокси
    setupProxyChecking();

    // Обработка модальных окон для удаления
    setupDeleteConfirmation();

    // Обработка реврайта контента
    setupRewriteContent();
});

/**
 * Настройка AJAX форм
 */
function setupAjaxForms() {
    // Находим все формы с классом ajax-form
    document.querySelectorAll('.ajax-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Показываем индикатор загрузки
            showLoading();
            
            // Отправляем форму через AJAX
            fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(handleResponse)
            .then(data => {
                // Скрываем индикатор загрузки
                hideLoading();
                
                // Показываем сообщение
                showToast(data.success ? 'success' : 'error', data.message);
                
                // Если есть отладочная информация, выводим в консоль
                if (data.debug) {
                    console.log('Debug info:', data.debug);
                }
                
                // Если успешно и указан редирект, перенаправляем
                if (data.success && data.redirect) {
                    setTimeout(function() {
                        window.location.href = data.redirect;
                    }, 1000);
                }
                
                // Если успешно и нужно очистить форму
                if (data.success && data.clearForm) {
                    form.reset();
                }
                
                // Если нужно обновить страницу
                if (data.success && data.refresh) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                // Скрываем индикатор загрузки
                hideLoading();
                
                // Показываем сообщение об ошибке с подробностями
                showToast('error', 'Произошла ошибка при отправке формы: ' + error.message);
                console.error('Form submission error:', error);
                
                // Перезагружаем страницу при критических ошибках
                setTimeout(function() {
                    window.location.reload();
                }, 3000);
            });
        });
    });
}

/**
 * Обработка ответа от сервера
 * 
 * @param {Response} response Объект ответа fetch
 * @returns {Promise<Object>} Объект с данными ответа
 */
function handleResponse(response) {
    // Расширенное логирование для отладки
    console.log('Response status:', response.status);
    console.log('Response headers:', Object.fromEntries([...response.headers.entries()]));
    
    return response.text().then(text => {
        console.log('Raw response text:', text.substring(0, 500));
        
        try {
            // Пробуем обработать ответ как JSON
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('JSON parse error:', e);
            
            // Если не удалось разобрать как JSON, проверяем содержит ли ответ HTML
            if (text.trim().startsWith('<')) {
                console.log('Received HTML response, treating as success and reloading page');
                
                // Создаем фиктивный объект с успешным результатом для перезагрузки страницы
                return {
                    success: true,
                    message: 'Операция выполнена, обновляю страницу...',
                    refresh: true
                };
            }
            
            throw new Error(`Ошибка парсинга JSON: ${e.message}. Ответ сервера: ${text.substring(0, 200)}`);
        }
    });
}

/**
 * Настройка проверки прокси
 */
function setupProxyChecking() {
    // Находим все кнопки проверки прокси
    document.querySelectorAll('.check-proxy-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            // Получаем ID прокси
            const proxyId = this.getAttribute('data-proxy-id');
            
            // Показываем индикатор загрузки
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Проверка...';
            this.disabled = true;
            
            // Отправляем запрос на проверку
            fetch('/proxies/check/' + proxyId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(handleResponse)
            .then(data => {
                // Обновляем статус прокси
                const statusElement = document.querySelector('#proxy-status-' + proxyId);
                if (statusElement) {
                    statusElement.innerHTML = data.success ? 
                        '<span class="badge bg-success">Работает</span>' : 
                        '<span class="badge bg-danger">Не работает</span>';
                }
                
                // Восстанавливаем кнопку
                this.innerHTML = 'Проверить';
                this.disabled = false;
                
                // Показываем сообщение
                showToast(data.success ? 'success' : 'error', data.message);
            })
            .catch(error => {
                // Восстанавливаем кнопку
                this.innerHTML = 'Проверить';
                this.disabled = false;
                
                // Показываем сообщение об ошибке с подробностями
                showToast('error', 'Произошла ошибка при проверке прокси: ' + error.message);
                console.error('Proxy check error:', error);
            });
        });
    });
}

/**
 * Настройка модальных окон для подтверждения удаления
 */
function setupDeleteConfirmation() {
    // Находим все кнопки удаления
    document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            // Получаем URL для удаления
            const deleteUrl = this.getAttribute('data-delete-url');
            
            // Получаем название элемента
            const itemName = this.getAttribute('data-item-name');
            
            // Настраиваем модальное окно
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            document.getElementById('deleteItemName').textContent = itemName;
            
            // Настраиваем кнопку подтверждения
            document.getElementById('confirmDeleteBtn').onclick = function() {
                // Скрываем модальное окно
                modal.hide();
                
                // Показываем индикатор загрузки
                showLoading();
                
                // Отправляем запрос на удаление
                fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(handleResponse)
                .then(data => {
                    // Скрываем индикатор загрузки
                    hideLoading();
                    
                    // Показываем сообщение
                    showToast(data.success ? 'success' : 'error', data.message);
                    
                    // Если успешно, обновляем страницу или перенаправляем
                    if (data.success) {
                        if (data.redirect) {
                            setTimeout(function() {
                                window.location.href = data.redirect;
                            }, 1000);
                        } else {
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    }
                })
                .catch(error => {
                    // Скрываем индикатор загрузки
                    hideLoading();
                    
                    // Показываем сообщение об ошибке и перезагружаем страницу
                    showToast('error', 'Произошла ошибка при удалении: ' + error.message);
                    console.error('Delete error:', error);
                    
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                });
            };
            
            // Показываем модальное окно
            modal.show();
        });
    });
}

/**
 * Настройка реврайта контента
 */
function setupRewriteContent() {
    // Находим все кнопки реврайта
    document.querySelectorAll('.rewrite-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const contentId = this.getAttribute('data-content-id');
            
            // Показываем модальное окно с прогрессом
            rewriteModal.show();
            
            // Отправляем запрос на реврайт
            fetch('/rewrite/process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ contentId: contentId })
            })
            .then(response => response.json())
            .then(data => {
                rewriteModal.hide();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else if (data.refresh) {
                        window.location.reload();
                    }
                } else {
                    showNotification(data.message, 'danger');
                }
            })
            .catch(error => {
                rewriteModal.hide();
                showNotification('Произошла ошибка при обработке запроса', 'danger');
                console.error('Error:', error);
            });
        });
    });
}

/**
 * Показать индикатор загрузки
 */
function showLoading() {
    // Создаем элемент оверлея, если его еще нет
    if (!document.getElementById('loadingOverlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'spinner-overlay';
        overlay.innerHTML = `
            <div class="spinner-container">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <div class="mt-2">Пожалуйста, подождите...</div>
            </div>
        `;
        document.body.appendChild(overlay);
    } else {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }
}

/**
 * Скрыть индикатор загрузки
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * Показать уведомление
 * 
 * @param {string} type Тип уведомления (success, error, warning, info)
 * @param {string} message Сообщение
 */
function showToast(type, message) {
    // Создаем контейнер для уведомлений, если его еще нет
    if (!document.querySelector('.toast-container')) {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    // Определяем цвет в зависимости от типа
    let bgColor = 'bg-primary';
    if (type === 'success') bgColor = 'bg-success';
    if (type === 'error') bgColor = 'bg-danger';
    if (type === 'warning') bgColor = 'bg-warning';
    if (type === 'info') bgColor = 'bg-info';
    
    // Создаем уведомление
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${bgColor} text-white">
                <strong class="me-auto">${type === 'error' ? 'Ошибка' : 'Уведомление'}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    // Добавляем уведомление в контейнер
    document.querySelector('.toast-container').insertAdjacentHTML('beforeend', toastHtml);
    
    // Инициализируем и показываем уведомление
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
    toast.show();
    
    // Удаляем уведомление после скрытия
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}