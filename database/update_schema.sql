-- Обновление схемы базы данных для поддержки изображений в постах

-- Добавление таблицы для хранения сгенерированных изображений
CREATE TABLE IF NOT EXISTS generated_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rewritten_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    prompt TEXT NOT NULL,
    width INT DEFAULT 512,
    height INT DEFAULT 512,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rewritten_id) REFERENCES rewritten_content(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Добавление поля для связи с изображением в таблице posts
ALTER TABLE posts ADD COLUMN image_id INT NULL;
ALTER TABLE posts ADD CONSTRAINT fk_posts_image FOREIGN KEY (image_id) REFERENCES generated_images(id) ON DELETE SET NULL;

-- Добавление настроек для API генерации изображений
INSERT INTO settings (setting_key, setting_value) VALUES 
('huggingface_api_key', ''),
('image_generation_model', 'stabilityai/stable-diffusion-3-medium-diffusers'),
('image_generation_enabled', '1'),
('image_width', '512'),
('image_height', '512'),
('image_prompt_template', 'Create a professional, high-quality image that represents the following content: {content}');
