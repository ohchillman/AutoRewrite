<?php
/**
 * Контроллер для управления настройками парсинга
 */
class ParsingController extends BaseController {
    
    /**
     * Отображение страницы настроек парсинга
     */
    public function index() {
        // Получаем список источников парсинга
        $sources = $this->getAllSources();
        
        // Получаем список прокси для выбора
        $proxies = $this->getProxies();
        
        // Отображаем представление
        $this->render('parsing/index', [
            'title' => 'Настройки парсинга - AutoRewrite',
            'pageTitle' => 'Настройки парсинга',
            'currentPage' => 'parsing',
            'layout' => 'main',
            'sources' => $sources,
            'proxies' => $proxies
        ]);
    }
    
    /**
     * Добавление нового источника парсинга
     */
    public function add() {
        // Проверяем, что запрос отправлен методом POST
        if (!$this->isMethod('POST')) {
            return $this->handleAjaxError('Метод не поддерживается', 405);
        }
        
        try {
            // Получаем данные из POST
            $name = $this->post('name');
            $url = $this->post('url');
            $sourceType = $this->post('source_type');
            $parsingFrequency = $this->post('parsing_frequency');
            $proxyId = $this->post('proxy_id');
            $additionalSettings = $this->post('additional_settings');
            
            // Проверяем обязательные поля
            if (empty($name) || empty($url) || empty($sourceType)) {
                return $this->handleAjaxError('Необходимо заполнить поля Название, URL и Тип источника');
            }
            
            // Валидация URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return $this->handleAjaxError('Некорректный URL источника');
            }
            
            // Проверяем, что тип источника допустимый
            $validSourceTypes = ['rss', 'blog'];
            if (!in_array($sourceType, $validSourceTypes)) {
                return $this->handleAjaxError('Недопустимый тип источника. Доступны только RSS и Blog');
            }
            
            // Подготавливаем данные для вставки
            $sourceData = [
                'name' => $name,
                'url' => $url,
                'source_type' => $sourceType,
                'parsing_frequency' => $parsingFrequency ?: 60,
                'proxy_id' => $proxyId ?: null,
                'additional_settings' => $additionalSettings ? json_encode($additionalSettings) : null,
                'is_active' => 1
            ];
            
            // Добавляем источник в базу данных
            $sourceId = $this->db->insert('parsing_sources', $sourceData);
            
            // Проверяем результат
            if ($sourceId) {
                Logger::info("Added new parsing source: {$name} ({$url}), Type: {$sourceType}", 'parsing');
                return $this->handleSuccess('Источник успешно добавлен', null, true);
            } else {
                return $this->handleAjaxError('Ошибка при добавлении источника');
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при добавлении источника: ' . $e->getMessage(), 'parsing');
            return $this->handleAjaxError('Ошибка при добавлении источника: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Удаление источника парсинга
     * 
     * @param int $id ID источника
     */
    public function delete($id = null) {
        // Проверяем ID
        if (empty($id)) {
            return $this->handleAjaxError('ID источника не указан', 400);
        }
        
        try {
            // Проверяем, что запрос отправлен методом POST
            if (!$this->isMethod('POST')) {
                return $this->handleAjaxError('Метод не поддерживается', 405);
            }
            
            // Удаляем источник из базы данных
            $result = $this->db->delete('parsing_sources', 'id = ?', [$id]);
            
            // Проверяем результат
            if ($result) {
                Logger::info("Deleted parsing source ID: {$id}", 'parsing');
                return $this->handleSuccess('Источник успешно удален', null, true);
            } else {
                return $this->handleAjaxError('Ошибка при удалении источника');
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при удалении источника: ' . $e->getMessage(), 'parsing');
            return $this->handleAjaxError('Ошибка при удалении источника: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Изменение статуса источника (активен/неактивен)
     * 
     * @param int $id ID источника
     */
    public function toggle($id = null) {
        // Проверяем ID
        if (empty($id)) {
            if ($this->isAjax()) {
                return $this->handleAjaxError('ID источника не указан', 400);
            } else {
                $_SESSION['error'] = 'ID источника не указан';
                $this->redirect('/parsing');
                return;
            }
        }
        
        try {
            // Получаем текущий статус
            $source = $this->db->fetchOne("SELECT is_active FROM parsing_sources WHERE id = ?", [$id]);
            
            if (!$source) {
                if ($this->isAjax()) {
                    return $this->handleAjaxError('Источник не найден', 404);
                } else {
                    $_SESSION['error'] = 'Источник не найден';
                    $this->redirect('/parsing');
                    return;
                }
            }
            
            // Инвертируем статус
            $newStatus = $source['is_active'] ? 0 : 1;
            
            // Обновляем статус в базе данных
            $result = $this->db->update('parsing_sources', ['is_active' => $newStatus], 'id = ?', [$id]);
            
            // Проверяем результат
            if ($result) {
                $statusText = $newStatus ? 'активирован' : 'деактивирован';
                Logger::info("Source ID: {$id} {$statusText}", 'parsing');
                
                if ($this->isAjax()) {
                    return $this->jsonResponse([
                        'success' => true,
                        'message' => "Источник успешно {$statusText}",
                        'refresh' => true
                    ]);
                } else {
                    $_SESSION['success'] = "Источник успешно {$statusText}";
                    $this->redirect('/parsing');
                    return;
                }
            } else {
                if ($this->isAjax()) {
                    return $this->handleAjaxError('Ошибка при изменении статуса источника');
                } else {
                    $_SESSION['error'] = 'Ошибка при изменении статуса источника';
                    $this->redirect('/parsing');
                    return;
                }
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при изменении статуса источника: ' . $e->getMessage(), 'parsing');
            if ($this->isAjax()) {
                return $this->handleAjaxError('Ошибка при изменении статуса источника: ' . $e->getMessage(), 500);
            } else {
                $_SESSION['error'] = 'Ошибка при изменении статуса источника: ' . $e->getMessage();
                $this->redirect('/parsing');
                return;
            }
        }
    }
    
    /**
     * Запуск парсинга источника вручную
     * 
     * @param int $id ID источника
     */
    public function parse($id) {
        // Проверяем ID
        if (empty($id)) {
            if ($this->isAjax()) {
                return $this->handleAjaxError('ID источника не указан', 400);
            } else {
                $_SESSION['error'] = 'ID источника не указан';
                $this->redirect('/parsing');
                return;
            }
        }
        
        try {
            // Получаем данные источника
            $source = $this->db->fetchOne("
                SELECT 
                    ps.*, 
                    p.id as proxy_id, 
                    p.ip as proxy_ip, 
                    p.port as proxy_port, 
                    p.username as proxy_username, 
                    p.password as proxy_password,
                    p.protocol as proxy_protocol,
                    p.is_active as proxy_active
                FROM parsing_sources ps
                LEFT JOIN proxies p ON ps.proxy_id = p.id
                WHERE ps.id = ?
            ", [$id]);

            Logger::debug("Источник парсинга", 'parsing', [
                'Source Details' => [
                    'ID' => $source['id'],
                    'URL' => $source['url'],
                    'Proxy ID' => $source['proxy_id'],
                    'Proxy IP' => $source['proxy_ip'],
                    'Proxy Port' => $source['proxy_port'],
                    'Proxy Protocol' => $source['proxy_protocol'],
                    'Proxy Active' => $source['proxy_active']
                ]
            ]);
            
            if (!$source) {
                if ($this->isAjax()) {
                    return $this->handleAjaxError('Источник не найден', 404);
                } else {
                    $_SESSION['error'] = 'Источник не найден';
                    $this->redirect('/parsing');
                    return;
                }
            }
            
            // Декодируем дополнительные настройки
            $additionalSettings = !empty($source['additional_settings']) ? 
                json_decode($source['additional_settings'], true) : [];
            
            // Определяем настройки для парсинга
            $limit = $additionalSettings['items'] ?? 20;
            $fetchFullContent = isset($additionalSettings['full_content']) ? 
                (bool)$additionalSettings['full_content'] : false;
            
            $proxyConfig = null;
            if (!empty($source['proxy_id']) && 
                !empty($source['proxy_ip']) && 
                $source['proxy_active'] == 1) {
                $proxyConfig = [
                    'host' => $source['proxy_ip'],
                    'port' => $source['proxy_port'],
                    'username' => $source['proxy_username'] ?? null,
                    'password' => $source['proxy_password'] ?? null,
                    'protocol' => $source['proxy_protocol'] ?? 'http'
                ];
            } else {
                Logger::warning("Прокси не может быть использован", 'parsing', [
                    'Proxy ID' => $source['proxy_id'],
                    'Proxy IP' => $source['proxy_ip'],
                    'Proxy Active' => $source['proxy_active']
                ]);
            }
            
            // Создаем парсер
            $parser = new ContentParser();
            
            // Выполняем парсинг в зависимости от типа источника
            $items = [];
            switch ($source['source_type']) {
                case 'rss':
                    Logger::info("Parsing RSS feed: {$source['url']}", 'parsing');
                    $items = $parser->parseRss(
                        $source['url'], 
                        $limit, 
                        $fetchFullContent,
                        $proxyConfig
                    );
                    break;
                
                case 'blog':
                    Logger::info("Parsing blog/news website: {$source['url']}", 'parsing');
                    $selectors = isset($additionalSettings['selectors']) ? 
                        $additionalSettings['selectors'] : [];
                    $items = $parser->parseBlog(
                        $source['url'], 
                        $selectors, 
                        $limit, 
                        $fetchFullContent
                    );
                    break;
                
                default:
                    throw new Exception("Неподдерживаемый тип источника: {$source['source_type']}");
            }
            
            // Проверяем на ошибки
            if (isset($items['error'])) {
                throw new Exception("Ошибка при парсинге: " . $items['error']);
            }
            
            Logger::info('Найдено ' . count($items) . ' элементов из источника: ' . $source['name'], 'parsing');
            
            // Сохранение полученных записей в базу данных
            $savedCount = 0;
            foreach ($items as $item) {
                // Проверка на пустые или невалидные данные
                if (empty($item['title']) || empty($item['link'])) {
                    Logger::warning("Пропуск элемента с пустым заголовком или ссылкой", 'parsing');
                    continue;
                }
                
                // Проверка, существует ли уже такая запись
                $exists = $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM original_content WHERE url = ? OR (title = ? AND source_id = ?)", 
                    [$item['link'], $item['title'], $source['id']]
                );
                
                if ($exists) {
                    Logger::debug("Элемент уже существует: " . $item['title'], 'parsing');
                    continue;
                }
                
                try {
                    // Подготовка данных для сохранения
                    $data = [
                        'source_id' => $source['id'],
                        'title' => $item['title'],
                        'content' => $item['description'],
                        'url' => $item['link'],
                        'author' => $item['author'] ?? '',
                        'published_date' => date('Y-m-d H:i:s', $item['timestamp']),
                        'parsed_at' => date('Y-m-d H:i:s'),
                        'is_processed' => 0,
                        'media_urls' => $this->extractMediaUrls($item['description'])
                    ];
                    
                    // Сохраняем контент
                    $contentId = $this->db->insert('original_content', $data);
                    
                    if ($contentId) {
                        $savedCount++;
                        Logger::info("Сохранен новый элемент с ID: {$contentId}, Заголовок: {$item['title']}", 'parsing');
                    } else {
                        Logger::warning("Не удалось сохранить элемент: {$item['title']}", 'parsing');
                    }
                } catch (Exception $e) {
                    Logger::error("Ошибка сохранения элемента: " . $e->getMessage(), 'parsing');
                }
            }
            
            // Обновляем время последнего парсинга
            $this->db->update('parsing_sources', 
                ['last_parsed' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$source['id']]
            );
            
            // Формируем ответ
            $message = "Парсинг источника \"{$source['name']}\" выполнен успешно. Добавлено записей: {$savedCount}";
            Logger::info($message, 'parsing');
            
            if ($this->isAjax()) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'savedCount' => $savedCount,
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = $message;
                $this->redirect('/parsing');
                return;
            }
        } catch (Exception $e) {
            // Обработка исключений
            $errorMessage = 'Ошибка при парсинге источника: ' . $e->getMessage();
            Logger::error($errorMessage, 'parsing');
            
            if ($this->isAjax()) {
                return $this->handleAjaxError($errorMessage, 500);
            } else {
                $_SESSION['error'] = $errorMessage;
                $this->redirect('/parsing');
                return;
            }
        }
    }
    
    /**
     * Редактирование источника парсинга
     * 
     * @param int $id ID источника
     */
    public function edit($id = null) {
        // Проверяем ID
        if (empty($id)) {
            $this->redirect('/parsing');
            return;
        }
        
        // Если POST запрос, обрабатываем форму
        if ($this->isMethod('POST')) {
            try {
                // Получаем данные из POST
                $name = $this->post('name');
                $url = $this->post('url');
                $sourceType = $this->post('source_type');
                $parsingFrequency = $this->post('parsing_frequency');
                $proxyId = $this->post('proxy_id');
                $additionalSettings = $this->post('additional_settings');
                
                // Проверяем обязательные поля
                if (empty($name) || empty($url) || empty($sourceType)) {
                    return $this->handleAjaxError('Необходимо заполнить поля Название, URL и Тип источника');
                }
                
                // Валидация URL
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    return $this->handleAjaxError('Некорректный URL источника');
                }
                
                // Проверяем, что тип источника допустимый
                $validSourceTypes = ['rss', 'blog'];
                if (!in_array($sourceType, $validSourceTypes)) {
                    return $this->handleAjaxError('Недопустимый тип источника. Доступны только RSS и Blog');
                }
                
                // Подготавливаем данные для обновления
                $sourceData = [
                    'name' => $name,
                    'url' => $url,
                    'source_type' => $sourceType,
                    'parsing_frequency' => $parsingFrequency ?: 60,
                    'proxy_id' => $proxyId ?: null,
                    'additional_settings' => $additionalSettings ? json_encode($additionalSettings) : null
                ];
                
                // Обновляем источник в базе данных
                $result = $this->db->update('parsing_sources', $sourceData, 'id = ?', [$id]);
                
                // Проверяем результат
                if ($result !== false) {
                    Logger::info("Updated parsing source ID: {$id}", 'parsing');
                    
                    if ($this->isAjax()) {
                        return $this->jsonResponse([
                            'success' => true,
                            'message' => 'Источник успешно обновлен',
                            'redirect' => '/parsing'
                        ]);
                    } else {
                        $_SESSION['success'] = 'Источник успешно обновлен';
                        $this->redirect('/parsing');
                    }
                } else {
                    return $this->handleAjaxError('Ошибка при обновлении источника');
                }
            } catch (Exception $e) {
                Logger::error('Ошибка при обновлении источника: ' . $e->getMessage(), 'parsing');
                return $this->handleAjaxError('Ошибка при обновлении источника: ' . $e->getMessage(), 500);
            }
        }
        
        // Получаем данные источника
        $source = $this->db->fetchOne("
            SELECT ps.*, p.ip as proxy_ip, p.port as proxy_port
            FROM parsing_sources ps
            LEFT JOIN proxies p ON ps.proxy_id = p.id
            WHERE ps.id = ?
        ", [$id]);
        
        if (!$source) {
            $_SESSION['error'] = 'Источник не найден';
            $this->redirect('/parsing');
            return;
        }
        
        // Получаем список прокси для выбора
        $proxies = $this->getProxies();
        
        // Отображаем представление
        $this->render('parsing/edit', [
            'title' => 'Редактирование источника - AutoRewrite',
            'pageTitle' => 'Редактирование источника парсинга',
            'currentPage' => 'parsing',
            'layout' => 'main',
            'source' => $source,
            'proxies' => $proxies
        ]);
    }
    
    /**
     * Извлекает URLs медиа-контента из HTML
     * 
     * @param string $content HTML-контент
     * @return string JSON-строка с URLs изображений
     */
    private function extractMediaUrls($content) {
        $mediaUrls = [];
        
        // Извлечение URL изображений
        preg_match_all('/<img[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $content, $matches);
        if (isset($matches['src']) && !empty($matches['src'])) {
            $mediaUrls['images'] = array_values(array_unique($matches['src']));
        }
        
        return !empty($mediaUrls) ? json_encode($mediaUrls) : null;
    }
    
    /**
     * Получение всех источников парсинга
     * 
     * @return array Массив источников
     */
    private function getAllSources() {
        try {
            return $this->db->fetchAll("
                SELECT ps.*, p.ip as proxy_ip, p.port as proxy_port
                FROM parsing_sources ps
                LEFT JOIN proxies p ON ps.proxy_id = p.id
                ORDER BY ps.is_active DESC, ps.name ASC
            ");
        } catch (Exception $e) {
            Logger::error('Ошибка при получении списка источников: ' . $e->getMessage(), 'parsing');
            return [];
        }
    }
    
    /**
     * Получение всех активных прокси
     * 
     * @return array Массив прокси
     */
    private function getProxies() {
        try {
            return $this->db->fetchAll("
                SELECT * FROM proxies 
                WHERE is_active = 1
                ORDER BY status = 'working' DESC, ip ASC
            ");
        } catch (Exception $e) {
            Logger::error('Ошибка при получении списка прокси: ' . $e->getMessage(), 'parsing');
            return [];
        }
    }
}