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
     * Просмотр контента и его реврайтнутых версий
     * 
     * @param int $id ID контента
     */
    public function view($id = null) {
        // Проверяем ID
        if (empty($id)) {
            $this->redirect('/rewrite');
            return;
        }
        
        // Проверяем, есть ли этот ID в таблице rewritten_content
        $rewrittenContent = $this->db->fetchOne("SELECT * FROM rewritten_content WHERE id = ?", [$id]);
        
        if ($rewrittenContent) {
            // Если это ID реврайтнутого контента, получаем ID оригинала
            $originalId = $rewrittenContent['original_id'];
        } else {
            // Иначе считаем, что это ID оригинального контента
            $originalId = $id;
        }
        
        // Получаем данные оригинального контента
        $originalContent = $this->db->fetchOne("
            SELECT o.*, ps.name as source_name, ps.source_type
            FROM original_content o
            JOIN parsing_sources ps ON o.source_id = ps.id
            WHERE o.id = ?
        ", [$originalId]);
        
        if (!$originalContent) {
            $_SESSION['error'] = 'Контент не найден';
            $this->redirect('/rewrite');
            return;
        }
        
        // Получаем все реврайтнутые версии этого контента
        $rewrittenVersions = $this->db->fetchAll("
            SELECT * FROM rewritten_content 
            WHERE original_id = ? 
            ORDER BY rewrite_date DESC
        ", [$originalId]);
        
        // Получаем список аккаунтов для постинга
        $accounts = $this->getActiveAccounts();
        
        // Получаем ID версии из GET-параметра (если указан)
        $selectedVersionId = isset($_GET['version']) ? intval($_GET['version']) : 
                        (!empty($rewrittenVersions) ? $rewrittenVersions[0]['id'] : 0);
        
        // Получаем историю постов для всех версий этого контента
        $posts = $this->db->fetchAll("
            SELECT p.*, r.id as version_id, a.name as account_name, a.account_type_id, at.name as account_type_name
            FROM posts p
            JOIN rewritten_content r ON p.rewritten_id = r.id
            JOIN accounts a ON p.account_id = a.id
            JOIN account_types at ON a.account_type_id = at.id
            WHERE r.original_id = ?
            ORDER BY p.posted_at DESC
        ", [$originalId]);
        
        // Отображаем представление
        $this->render('rewrite/view', [
            'title' => 'Просмотр контента - AutoRewrite',
            'pageTitle' => 'Просмотр контента и его версий',
            'currentPage' => 'rewrite',
            'layout' => 'main',
            'originalContent' => $originalContent,
            'rewrittenVersions' => $rewrittenVersions,
            'selectedVersionId' => $selectedVersionId,
            'accounts' => $accounts,
            'posts' => $posts
        ]);
    }

    /**
     * Удаление версии реврайтнутого контента
     * 
     * @param int $id ID реврайтнутого контента
     */
    public function deleteVersion($id = null) {
        // Проверяем ID
        if (empty($id)) {
            return $this->handleAjaxError('ID версии не указан', 400);
        }
        
        try {
            // Проверяем, что запрос отправлен методом POST
            if (!$this->isMethod('POST')) {
                return $this->handleAjaxError('Метод не поддерживается', 405);
            }
            
            // Получаем данные о версии, чтобы знать ID оригинала
            $version = $this->db->fetchOne("
                SELECT original_id FROM rewritten_content WHERE id = ?
            ", [$id]);
            
            if (!$version) {
                return $this->handleAjaxError('Версия не найдена', 404);
            }
            
            $originalId = $version['original_id'];
            
            // Проверяем, есть ли другие версии для этого оригинала
            $otherVersionsCount = $this->db->fetchColumn("
                SELECT COUNT(*) FROM rewritten_content 
                WHERE original_id = ? AND id != ?
            ", [$originalId, $id]);
            
            // Начинаем транзакцию
            $this->db->getConnection()->beginTransaction();
            
            try {
                // Удаляем все посты, связанные с этой версией
                $this->db->delete('posts', 'rewritten_id = ?', [$id]);
                
                // Удаляем версию
                $result = $this->db->delete('rewritten_content', 'id = ?', [$id]);
                
                // Если других версий нет, сбрасываем флаг is_processed у оригинала
                if ($otherVersionsCount == 0) {
                    $this->db->update('original_content', [
                        'is_processed' => 0,
                        // Счетчик реврайтов не изменяем, так как он показывает историю
                    ], 'id = ?', [$originalId]);
                }
                
                // Фиксируем транзакцию
                $this->db->getConnection()->commit();
                
                // Проверяем результат
                if ($result) {
                    return $this->handleSuccess('Версия успешно удалена', '/rewrite/view/' . $originalId);
                } else {
                    $this->db->getConnection()->rollBack();
                    return $this->handleAjaxError('Ошибка при удалении версии');
                }
            } catch (Exception $e) {
                $this->db->getConnection()->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при удалении версии: ' . $e->getMessage(), 'rewrite');
            return $this->handleAjaxError('Ошибка при удалении версии: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Метод для обработки реврайта контента
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
                SELECT * FROM original_content WHERE id = ?
            ", [$id]);
            
            if (!$originalContent) {
                return $this->handleAjaxError('Контент не найден', 404);
            }
            
            // Получаем настройки для реврайта
            $settings = $this->getSettings();
            
            // Определяем провайдера API и проверяем API-ключ
            $aiProvider = $settings['ai_provider'] ?? 'gemini';
            $apiKey = '';
            
            if ($aiProvider === 'gemini') {
                $apiKey = $settings['gemini_api_key'] ?? '';
                if (empty($apiKey)) {
                    return $this->handleAjaxError('API-ключ Gemini не настроен. Пожалуйста, добавьте его в настройках.', 400);
                }
            } else if ($aiProvider === 'openrouter') {
                $apiKey = $settings['openrouter_api_key'] ?? '';
                if (empty($apiKey)) {
                    return $this->handleAjaxError('API-ключ OpenRouter не настроен. Пожалуйста, добавьте его в настройках.', 400);
                }
            } else {
                return $this->handleAjaxError('Неизвестный провайдер API: ' . $aiProvider, 400);
            }
            
            // Получаем шаблон для реврайта
            $rewriteTemplate = $settings['rewrite_template'] ?? 'Перепиши следующий текст, сохраняя смысл, но изменяя формулировки: {content}';
            
            // Создаем экземпляр клиента API
            require_once UTILS_PATH . '/GeminiApiClient.php';
            $geminiClient = new GeminiApiClient(
                $apiKey,
                $settings['gemini_model'] ?? 'gemini-pro:free',
                $aiProvider === 'openrouter' // Используем OpenRouter, если выбран этот провайдер
            );
            
            // Подготавливаем контент для реврайта
            $contentToRewrite = $originalContent['title'] . "\n\n" . $originalContent['content'];
            
            // Отправка запроса на реврайт
            Logger::info("Отправка запроса на реврайт контента ID: {$originalContent['id']} через {$aiProvider}", 'rewrite');
            $response = $geminiClient->rewriteContent($contentToRewrite, $rewriteTemplate);
            
            // Проверяем успешность запроса
            if (!$response['success']) {
                $errorMessage = "Ошибка при запросе к API: " . ($response['error'] ?? 'Неизвестная ошибка');
                Logger::error($errorMessage, 'rewrite');
                return $this->handleAjaxError($errorMessage, 500);
            }
            
            // Обработка ответа от API
            $rewrittenContent = $response['content'];
            
            // Если ответ пустой, возвращаем ошибку
            if (empty($rewrittenContent)) {
                Logger::error("Пустой ответ от API при реврайте контента ID: {$originalContent['id']}", 'rewrite');
                return $this->handleAjaxError('Получен пустой ответ от API. Попробуйте еще раз.', 500);
            }
            
            // Разделение заголовка и описания (первый абзац - заголовок, остальное - контент)
            $parts = explode("\n\n", $rewrittenContent, 2);
            
            $rewrittenTitle = trim($parts[0]);
            $rewrittenContent = isset($parts[1]) ? trim($parts[1]) : '';
            
            // Если не удалось разделить, используем оригинальный заголовок
            if (empty($rewrittenContent)) {
                $rewrittenContent = $rewrittenTitle;
                $rewrittenTitle = $originalContent['title'];
            }
            
            // Используем транзакцию для обеспечения согласованности данных
            $this->db->getConnection()->beginTransaction();
            
            try {
                // Сохраняем реврайтнутый контент
                $rewrittenId = $this->db->insert('rewritten_content', [
                    'original_id' => $originalContent['id'],
                    'title' => $rewrittenTitle,
                    'content' => $rewrittenContent,
                    'rewrite_date' => date('Y-m-d H:i:s'),
                    'status' => 'rewritten'
                ]);
                
                // Проверяем результат
                if ($rewrittenId) {
                    // Увеличиваем счетчик реврайтов, но оставляем статус is_processed в 1
                    // чтобы показывать, что контент был обработан хотя бы один раз
                    $rewriteCount = intval($originalContent['rewrite_count']) + 1;
                    
                    $this->db->update('original_content', [
                        'is_processed' => 1,
                        'rewrite_count' => $rewriteCount
                    ], 'id = ?', [$originalContent['id']]);
                    
                    // Логируем успешный реврайт
                    Logger::info("Контент успешно реврайтнут через {$aiProvider}, ID оригинала: {$originalContent['id']}, ID реврайта: {$rewrittenId}, Счетчик реврайтов: {$rewriteCount}", 'rewrite');
                    
                    // Фиксируем транзакцию
                    $this->db->getConnection()->commit();
                    
                    // Возвращаем на страницу с оригинальным контентом и его версиями
                    return $this->handleSuccess('Контент успешно реврайтнут', '/rewrite/view/' . $originalContent['id'] . '?version=' . $rewrittenId);
                } else {
                    // Отменяем транзакцию в случае ошибки
                    $this->db->getConnection()->rollBack();
                    return $this->handleAjaxError('Ошибка при сохранении реврайтнутого контента');
                }
            } catch (Exception $e) {
                // Отменяем транзакцию в случае исключения
                $this->db->getConnection()->rollBack();
                throw $e; // Перебрасываем исключение для обработки во внешнем блоке try-catch
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