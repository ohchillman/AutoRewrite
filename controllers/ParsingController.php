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
                if ($this->isAjax()) {
                    return $this->jsonResponse([
                        'success' => true,
                        'message' => 'Статус источника изменен',
                        'refresh' => true
                    ]);
                } else {
                    $_SESSION['success'] = 'Статус источника изменен';
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
    public function parse($id = null) {
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
                SELECT * FROM parsing_sources WHERE id = ?
            ", [$id]);
            
            if (!$source) {
                if ($this->isAjax()) {
                    return $this->handleAjaxError('Источник не найден', 404);
                } else {
                    $_SESSION['error'] = 'Источник не найден';
                    $this->redirect('/parsing');
                    return;
                }
            }
            
            // Здесь будет логика парсинга источника
            // В реальном приложении это должно выполняться в фоновом режиме
            // Для примера просто обновим время последнего парсинга
            
            $result = $this->db->update('parsing_sources', [
                'last_parsed' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            
            // Имитируем добавление контента
            $contentId = $this->db->insert('original_content', [
                'source_id' => $id,
                'title' => 'Пример контента из ' . $source['name'],
                'content' => 'Это пример контента, который был бы получен при парсинге источника ' . $source['name'] . '. В реальном приложении здесь будет настоящий контент из источника.',
                'url' => $source['url'],
                'author' => 'Система',
                'published_date' => date('Y-m-d H:i:s'),
                'parsed_at' => date('Y-m-d H:i:s')
            ]);
            
            // Отправляем ответ
            if ($result && $contentId) {
                if ($this->isAjax()) {
                    return $this->jsonResponse([
                        'success' => true,
                        'message' => 'Парсинг источника выполнен успешно',
                        'refresh' => true
                    ]);
                } else {
                    $_SESSION['success'] = 'Парсинг источника выполнен успешно';
                    $this->redirect('/parsing');
                    return;
                }
            } else {
                if ($this->isAjax()) {
                    return $this->handleAjaxError('Ошибка при парсинге источника');
                } else {
                    $_SESSION['error'] = 'Ошибка при парсинге источника';
                    $this->redirect('/parsing');
                    return;
                }
            }
        } catch (Exception $e) {
            Logger::error('Ошибка при парсинге источника: ' . $e->getMessage(), 'parsing');
            if ($this->isAjax()) {
                return $this->handleAjaxError('Ошибка при парсинге источника: ' . $e->getMessage(), 500);
            } else {
                $_SESSION['error'] = 'Ошибка при парсинге источника: ' . $e->getMessage();
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