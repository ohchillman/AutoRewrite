<?php
/**
 * Контроллер для управления реврайтом контента
 */
class RewriteController extends BaseController {
    
    /**
     * Отображение страницы реврайта
     */
    public function index() {
        // Получаем список реврайтнутого контента
        $rewrittenContent = $this->getRewrittenContent();
        
        // Получаем список оригинального контента, который еще не был реврайтнут
        $originalContent = $this->getOriginalContent();
        
        // Отображаем представление
        $this->render('rewrite/index', [
            'title' => 'Реврайт - AutoRewrite',
            'pageTitle' => 'Управление реврайтом',
            'currentPage' => 'rewrite',
            'layout' => 'main',
            'rewrittenContent' => $rewrittenContent,
            'originalContent' => $originalContent
        ]);
    }
    
    /**
     * Просмотр реврайтнутого контента
     * 
     * @param int $id ID реврайтнутого контента
     */
    public function view($id = null) {
        // Проверяем ID
        if (empty($id)) {
            $this->redirect('/rewrite');
            return;
        }
        
        // Получаем данные реврайтнутого контента
        $content = $this->db->fetchOne("
            SELECT r.*, o.title as original_title, o.content as original_content, o.url as original_url, 
                  o.author as original_author, o.published_date as original_date, 
                  ps.name as source_name, ps.source_type
            FROM rewritten_content r
            JOIN original_content o ON r.original_id = o.id
            JOIN parsing_sources ps ON o.source_id = ps.id
            WHERE r.id = ?
        ", [$id]);
        
        if (!$content) {
            $_SESSION['error'] = 'Контент не найден';
            $this->redirect('/rewrite');
            return;
        }
        
        // Получаем список аккаунтов для постинга
        $accounts = $this->getActiveAccounts();
        
        // Получаем историю постов для этого контента
        $posts = $this->db->fetchAll("
            SELECT p.*, a.name as account_name, a.account_type_id, at.name as account_type_name
            FROM posts p
            JOIN accounts a ON p.account_id = a.id
            JOIN account_types at ON a.account_type_id = at.id
            WHERE p.rewritten_id = ?
            ORDER BY p.posted_at DESC
        ", [$id]);
        
        // Отображаем представление
        $this->render('rewrite/view', [
            'title' => 'Просмотр контента - AutoRewrite',
            'pageTitle' => 'Просмотр реврайтнутого контента',
            'currentPage' => 'rewrite',
            'layout' => 'main',
            'content' => $content,
            'accounts' => $accounts,
            'posts' => $posts
        ]);
    }
    
    /**
     * Реврайт контента
     * 
     * @param int $id ID оригинального контента
     */
    public function process($id = null) {
        try {
            // Получаем ID из параметров или из JSON тела запроса
            if (empty($id) && $this->isAjax()) {
                $data = $this->getJsonInput();
                $id = $data['contentId'] ?? null;
            }
            
            // Проверяем ID
            if (empty($id)) {
                return $this->handleAjaxError('ID контента не указан', 400);
            }
            
            // Получаем данные оригинального контента
            $originalContent = $this->db->fetchOne("
                SELECT * FROM original_content WHERE id = ? AND is_processed = 0
            ", [$id]);
            
            if (!$originalContent) {
                return $this->handleAjaxError('Контент не найден или уже был обработан', 404);
            }
            
            // Получаем настройки для реврайта
            $settings = $this->getSettings();
            $rewriteTemplate = $settings['rewrite_template'] ?? 'Перепиши следующий текст, сохраняя смысл, но изменяя формулировки: {content}';
            
            // Здесь должен быть код для отправки запроса на Make.com API
            // В реальном приложении это должно выполняться в фоновом режиме
            
            // Для примера просто модифицируем оригинальный контент
            $rewrittenTitle = 'Реврайт: ' . $originalContent['title'];
            $rewrittenContent = 'Это реврайтнутая версия оригинального контента: ' . $originalContent['content'];
            
            // Сохраняем реврайтнутый контент
            $rewrittenId = $this->db->insert('rewritten_content', [
                'original_id' => $originalContent['id'],
                'title' => $rewrittenTitle,
                'content' => $rewrittenContent,
                'rewrite_date' => date('Y-m-d H:i:s'),
                'status' => 'rewritten'
            ]);
            
            // Обновляем статус оригинального контента
            $this->db->update('original_content', [
                'is_processed' => 1
            ], 'id = ?', [$originalContent['id']]);
            
            // Проверяем результат
            if ($rewrittenId) {
                return $this->handleSuccess('Контент успешно реврайтнут', '/rewrite/view/' . $rewrittenId);
            } else {
                return $this->handleAjaxError('Ошибка при реврайте контента');
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при реврайте контента: ' . $e->getMessage(), 'rewrite');
            return $this->handleAjaxError('Ошибка при реврайте контента: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Публикация контента в аккаунт
     */
    public function publishPost() {
        // Проверяем, что запрос отправлен методом POST
        if (!$this->isMethod('POST')) {
            return $this->handleAjaxError('Метод не поддерживается', 405);
        }
        
        try {
            // Получаем данные из POST
            $rewrittenId = $this->post('rewritten_id');
            $accountId = $this->post('account_id');
            
            // Проверяем обязательные поля
            if (empty($rewrittenId) || empty($accountId)) {
                return $this->handleAjaxError('Необходимо указать ID контента и ID аккаунта');
            }
            
            // Получаем данные реврайтнутого контента
            $content = $this->db->fetchOne("
                SELECT * FROM rewritten_content WHERE id = ?
            ", [$rewrittenId]);
            
            if (!$content) {
                return $this->handleAjaxError('Контент не найден', 404);
            }
            
            // Получаем данные аккаунта
            $account = $this->db->fetchOne("
                SELECT a.*, at.name as account_type_name
                FROM accounts a
                JOIN account_types at ON a.account_type_id = at.id
                WHERE a.id = ? AND a.is_active = 1
            ", [$accountId]);
            
            if (!$account) {
                return $this->handleAjaxError('Аккаунт не найден или неактивен', 404);
            }
            
            // Здесь должен быть код для публикации контента в аккаунт
            // В реальном приложении это должно выполняться в фоновом режиме
            
            // Для примера просто создаем запись о публикации
            $postId = $this->db->insert('posts', [
                'rewritten_id' => $rewrittenId,
                'account_id' => $accountId,
                'post_url' => 'https://example.com/post/123',
                'posted_at' => date('Y-m-d H:i:s'),
                'status' => 'posted'
            ]);
            
            // Обновляем статус реврайтнутого контента
            $this->db->update('rewritten_content', [
                'is_posted' => 1,
                'status' => 'posted'
            ], 'id = ?', [$rewrittenId]);
            
            // Обновляем время последнего использования аккаунта
            $this->db->update('accounts', [
                'last_used' => date('Y-m-d H:i:s')
            ], 'id = ?', [$accountId]);
            
            // Проверяем результат
            if ($postId) {
                return $this->handleSuccess('Контент успешно опубликован в аккаунт ' . $account['name'], null, true);
            } else {
                return $this->handleAjaxError('Ошибка при публикации контента');
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при публикации контента: ' . $e->getMessage(), 'rewrite');
            return $this->handleAjaxError('Ошибка при публикации контента: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Удаление реврайтнутого контента
     * 
     * @param int $id ID реврайтнутого контента
     */
    public function delete($id = null) {
        // Проверяем ID
        if (empty($id)) {
            return $this->handleAjaxError('ID контента не указан', 400);
        }
        
        try {
            // Проверяем, что запрос отправлен методом POST
            if (!$this->isMethod('POST')) {
                return $this->handleAjaxError('Метод не поддерживается', 405);
            }
            
            // Получаем ID оригинального контента
            $originalId = $this->db->fetchColumn("
                SELECT original_id FROM rewritten_content WHERE id = ?
            ", [$id]);
            
            if (!$originalId) {
                return $this->handleAjaxError('Контент не найден', 404);
            }
            
            // Удаляем реврайтнутый контент
            $result = $this->db->delete('rewritten_content', 'id = ?', [$id]);
            
            // Обновляем статус оригинального контента
            $this->db->update('original_content', [
                'is_processed' => 0
            ], 'id = ?', [$originalId]);
            
            // Проверяем результат
            if ($result) {
                return $this->handleSuccess('Контент успешно удален', '/rewrite');
            } else {
                return $this->handleAjaxError('Ошибка при удалении контента');
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при удалении контента: ' . $e->getMessage(), 'rewrite');
            return $this->handleAjaxError('Ошибка при удалении контента: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Получение списка реврайтнутого контента
     * 
     * @return array Массив реврайтнутого контента
     */
    private function getRewrittenContent() {
        try {
            return $this->db->fetchAll("
                SELECT r.*, o.title as original_title, o.url as original_url, 
                      ps.name as source_name, ps.source_type
                FROM rewritten_content r
                JOIN original_content o ON r.original_id = o.id
                JOIN parsing_sources ps ON o.source_id = ps.id
                ORDER BY r.rewrite_date DESC
            ");
        } catch (Exception $e) {
            Logger::error('Ошибка при получении списка реврайтнутого контента: ' . $e->getMessage(), 'rewrite');
            return [];
        }
    }
    
    /**
     * Получение списка оригинального контента, который еще не был реврайтнут
     * 
     * @return array Массив оригинального контента
     */
    private function getOriginalContent() {
        try {
            return $this->db->fetchAll("
                SELECT o.*, ps.name as source_name, ps.source_type
                FROM original_content o
                JOIN parsing_sources ps ON o.source_id = ps.id
                WHERE o.is_processed = 0
                ORDER BY o.parsed_at DESC
            ");
        } catch (Exception $e) {
            Logger::error('Ошибка при получении списка оригинального контента: ' . $e->getMessage(), 'rewrite');
            return [];
        }
    }
    
    /**
     * Получение списка активных аккаунтов
     * 
     * @return array Массив аккаунтов
     */
    private function getActiveAccounts() {
        try {
            return $this->db->fetchAll("
                SELECT a.*, at.name as account_type_name
                FROM accounts a
                JOIN account_types at ON a.account_type_id = at.id
                WHERE a.is_active = 1
                ORDER BY a.name ASC
            ");
        } catch (Exception $e) {
            Logger::error('Ошибка при получении списка активных аккаунтов: ' . $e->getMessage(), 'rewrite');
            return [];
        }
    }
    
    /**
     * Получение настроек
     * 
     * @return array Массив настроек
     */
    private function getSettings() {
        try {
            $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM settings");
            
            // Преобразуем в ассоциативный массив
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting['setting_key']] = $setting['setting_value'];
            }
            
            return $result;
        } catch (Exception $e) {
            Logger::error('Ошибка при получении настроек: ' . $e->getMessage(), 'rewrite');
            return [];
        }
    }
}