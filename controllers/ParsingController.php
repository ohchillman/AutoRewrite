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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/parsing');
            return;
        }
        
        // Получаем данные из POST
        $name = $this->post('name');
        $url = $this->post('url');
        $sourceType = $this->post('source_type');
        $parsingFrequency = $this->post('parsing_frequency');
        $proxyId = $this->post('proxy_id');
        $additionalSettings = $this->post('additional_settings');
        
        // Проверяем обязательные поля
        if (empty($name) || empty($url) || empty($sourceType)) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Необходимо заполнить поля Название, URL и Тип источника'
                ]);
            } else {
                $_SESSION['error'] = 'Необходимо заполнить поля Название, URL и Тип источника';
                $this->redirect('/parsing');
            }
            return;
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
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Источник успешно добавлен',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Источник успешно добавлен';
                $this->redirect('/parsing');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при добавлении источника'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при добавлении источника';
                $this->redirect('/parsing');
            }
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
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'ID источника не указан'
                ]);
            } else {
                $_SESSION['error'] = 'ID источника не указан';
                $this->redirect('/parsing');
            }
            return;
        }
        
        // Удаляем источник из базы данных
        $result = $this->db->delete('parsing_sources', 'id = ?', [$id]);
        
        // Проверяем результат
        if ($result) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Источник успешно удален',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Источник успешно удален';
                $this->redirect('/parsing');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при удалении источника'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при удалении источника';
                $this->redirect('/parsing');
            }
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
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'ID источника не указан'
                ]);
            } else {
                $_SESSION['error'] = 'ID источника не указан';
                $this->redirect('/parsing');
            }
            return;
        }
        
        // Получаем текущий статус
        $source = $this->db->fetchOne("SELECT is_active FROM parsing_sources WHERE id = ?", [$id]);
        
        if (!$source) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Источник не найден'
                ]);
            } else {
                $_SESSION['error'] = 'Источник не найден';
                $this->redirect('/parsing');
            }
            return;
        }
        
        // Инвертируем статус
        $newStatus = $source['is_active'] ? 0 : 1;
        
        // Обновляем статус в базе данных
        $result = $this->db->update('parsing_sources', ['is_active' => $newStatus], 'id = ?', [$id]);
        
        // Проверяем результат
        if ($result) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Статус источника изменен',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Статус источника изменен';
                $this->redirect('/parsing');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при изменении статуса источника'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при изменении статуса источника';
                $this->redirect('/parsing');
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
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'ID источника не указан'
                ]);
            } else {
                $_SESSION['error'] = 'ID источника не указан';
                $this->redirect('/parsing');
            }
            return;
        }
        
        // Получаем данные источника
        $source = $this->db->fetchOne("
            SELECT * FROM parsing_sources WHERE id = ?
        ", [$id]);
        
        if (!$source) {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Источник не найден'
                ]);
            } else {
                $_SESSION['error'] = 'Источник не найден';
                $this->redirect('/parsing');
            }
            return;
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
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Парсинг источника выполнен успешно',
                    'refresh' => true
                ]);
            } else {
                $_SESSION['success'] = 'Парсинг источника выполнен успешно';
                $this->redirect('/parsing');
            }
        } else {
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при парсинге источника'
                ]);
            } else {
                $_SESSION['error'] = 'Ошибка при парсинге источника';
                $this->redirect('/parsing');
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Получаем данные из POST
            $name = $this->post('name');
            $url = $this->post('url');
            $sourceType = $this->post('source_type');
            $parsingFrequency = $this->post('parsing_frequency');
            $proxyId = $this->post('proxy_id');
            $additionalSettings = $this->post('additional_settings');
            
            // Проверяем обязательные поля
            if (empty($name) || empty($url) || empty($sourceType)) {
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Необходимо заполнить поля Название, URL и Тип источника'
                    ]);
                } else {
                    $_SESSION['error'] = 'Необходимо заполнить поля Название, URL и Тип источника';
                    $this->redirect('/parsing/edit/' . $id);
                }
                return;
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
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Источник успешно обновлен',
                        'redirect' => '/parsing'
                    ]);
                } else {
                    $_SESSION['success'] = 'Источник успешно обновлен';
                    $this->redirect('/parsing');
                }
            } else {
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Ошибка при обновлении источника'
                    ]);
                } else {
                    $_SESSION['error'] = 'Ошибка при обновлении источника';
                    $this->redirect('/parsing/edit/' . $id);
                }
            }
            return;
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
        return $this->db->fetchAll("
            SELECT ps.*, p.ip as proxy_ip, p.port as proxy_port
            FROM parsing_sources ps
            LEFT JOIN proxies p ON ps.proxy_id = p.id
            ORDER BY ps.is_active DESC, ps.name ASC
        ");
    }
    
    /**
     * Получение всех активных прокси
     * 
     * @return array Массив прокси
     */
    private function getProxies() {
        return $this->db->fetchAll("
            SELECT * FROM proxies 
            WHERE is_active = 1
            ORDER BY status = 'working' DESC, ip ASC
        ");
    }
}
