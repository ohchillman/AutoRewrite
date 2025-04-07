<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Тестирование генерации изображений</h5>
            </div>
            <div class="card-body">
                <form id="imageTestForm" class="mb-4">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="prompt" class="form-label">Запрос для генерации</label>
                                <textarea class="form-control" id="prompt" name="prompt" rows="3" required></textarea>
                                <div class="form-text">Опишите изображение, которое хотите сгенерировать</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="provider" class="form-label">API провайдер</label>
                                <select class="form-select" id="provider" name="provider">
                                    <option value="stable_diffusion" <?php echo ($settings['image_api_provider'] ?? '') == 'stable_diffusion' ? 'selected' : ''; ?>>Stable Diffusion</option>
                                    <option value="dalle" <?php echo ($settings['image_api_provider'] ?? '') == 'dalle' ? 'selected' : ''; ?>>DALL-E</option>
                                    <option value="midjourney" <?php echo ($settings['image_api_provider'] ?? '') == 'midjourney' ? 'selected' : ''; ?>>Midjourney</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="generateBtn">
                                    <i class="fas fa-magic"></i> Сгенерировать
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div id="resultContainer" style="display: none;">
                    <h5>Результат генерации:</h5>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="image-container">
                                <img id="generatedImage" src="" alt="Сгенерированное изображение" class="img-fluid mb-3">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Использованный запрос:</h6>
                                    <p id="usedPrompt" class="text-muted"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('imageTestForm');
    const generateBtn = document.getElementById('generateBtn');
    const resultContainer = document.getElementById('resultContainer');
    const generatedImage = document.getElementById('generatedImage');
    const usedPrompt = document.getElementById('usedPrompt');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Отключаем кнопку и показываем индикатор загрузки
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Генерация...';
        
        // Получаем значения полей
        const promptValue = document.getElementById('prompt').value.trim();
        const providerValue = document.getElementById('provider').value;
        
        // Проверяем, что промпт не пустой
        if (!promptValue) {
            alert('Пожалуйста, введите запрос для генерации изображения');
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fas fa-magic"></i> Сгенерировать';
            return;
        }
        
        // Отправляем запрос с правильными заголовками
        fetch('/image-test/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                prompt: promptValue,
                provider: providerValue
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Восстанавливаем кнопку
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fas fa-magic"></i> Сгенерировать';
            
            if (data.success) {
                // Показываем результат
                console.log("Изображение получено:", data.imageUrl); // Добавьте для отладки
                generatedImage.src = data.imageUrl;
                usedPrompt.textContent = data.prompt;
                resultContainer.style.display = 'block';
                resultContainer.scrollIntoView({ behavior: 'smooth' });
            } else {
                // Показываем сообщение об ошибке
                showNotification(data.message, 'danger');
            }
        })
        .catch(error => {
            // Восстанавливаем кнопку
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fas fa-magic"></i> Сгенерировать';
            
            // Показываем сообщение об ошибке
            showNotification('Ошибка при генерации изображения: ' + error.message, 'danger');
            console.error('Error:', error);
        });
    });
    
    // Функция для показа уведомлений (если не определена глобально)
    function showNotification(message, type) {
        // Создаем элемент уведомления
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Находим контейнер для уведомлений
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
