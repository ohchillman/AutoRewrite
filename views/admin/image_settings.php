<?php include VIEWS_PATH . '/admin/header.php'; ?>

<div class="container mt-4">
    <h1>Настройки генерации изображений</h1>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        Настройки успешно сохранены!
    </div>
    <?php endif; ?>
    
    <form method="post" action="/admin/image-settings/save">
        <div class="card mb-4">
            <div class="card-header">
                <h5>API настройки</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="huggingface_api_key" class="form-label">Hugging Face API ключ</label>
                    <input type="text" class="form-control" id="huggingface_api_key" name="huggingface_api_key" 
                           value="<?php echo htmlspecialchars($settings['huggingface_api_key'] ?? ''); ?>">
                    <div class="form-text">Получите ключ на сайте <a href="https://huggingface.co/settings/tokens" target="_blank">huggingface.co</a></div>
                </div>
                
                <div class="mb-3">
                    <label for="image_generation_model" class="form-label">Модель для генерации изображений</label>
                    <input type="text" class="form-control" id="image_generation_model" name="image_generation_model" 
                           value="<?php echo htmlspecialchars($settings['image_generation_model'] ?? 'stabilityai/stable-diffusion-3-medium-diffusers'); ?>">
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="image_generation_enabled" name="image_generation_enabled" value="1" 
                           <?php echo (isset($settings['image_generation_enabled']) && $settings['image_generation_enabled'] == '1') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="image_generation_enabled">Включить генерацию изображений</label>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Настройки изображений</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="image_width" class="form-label">Ширина изображения (px)</label>
                        <input type="number" class="form-control" id="image_width" name="image_width" 
                               value="<?php echo htmlspecialchars($settings['image_width'] ?? '512'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="image_height" class="form-label">Высота изображения (px)</label>
                        <input type="number" class="form-control" id="image_height" name="image_height" 
                               value="<?php echo htmlspecialchars($settings['image_height'] ?? '512'); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="image_prompt_template" class="form-label">Шаблон промпта для генерации</label>
                    <textarea class="form-control" id="image_prompt_template" name="image_prompt_template" rows="3"><?php echo htmlspecialchars($settings['image_prompt_template'] ?? 'Create a professional, high-quality image that represents the following content: {content}'); ?></textarea>
                    <div class="form-text">Используйте {content} для вставки содержимого поста</div>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Сохранить настройки</button>
    </form>
</div>

<?php include VIEWS_PATH . '/admin/footer.php'; ?>
