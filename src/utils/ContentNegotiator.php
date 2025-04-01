<?php
/**
 * Класс для обработки Content Negotiation
 * Обеспечивает правильную обработку AJAX и обычных запросов
 */
class ContentNegotiator {
    /**
     * Проверяет, является ли текущий запрос AJAX-запросом
     * 
     * @return bool Является ли запрос AJAX
     */
    public static function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Отправляет JSON-ответ и завершает выполнение скрипта
     * 
     * @param mixed $data Данные для отправки
     * @param int $statusCode HTTP-код ответа
     */
    public static function sendJsonResponse($data, $statusCode = 200) {
        // Очищаем все буферы вывода
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Устанавливаем заголовки
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        // Отправляем данные
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Обрабатывает ошибку в зависимости от типа запроса
     * 
     * @param string $message Сообщение об ошибке
     * @param int $statusCode HTTP-код ошибки
     * @param string $redirectUrl URL для перенаправления (для не-AJAX запросов)
     */
    public static function handleError($message, $statusCode = 400, $redirectUrl = null) {
        if (self::isAjaxRequest()) {
            self::sendJsonResponse([
                'success' => false,
                'message' => $message
            ], $statusCode);
        } else {
            if ($redirectUrl) {
                $_SESSION['error'] = $message;
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                http_response_code($statusCode);
                echo '<h1>Ошибка ' . $statusCode . '</h1>';
                echo '<p>' . $message . '</p>';
                exit;
            }
        }
    }
    
    /**
     * Обрабатывает успешный результат в зависимости от типа запроса
     * 
     * @param string $message Сообщение об успехе
     * @param mixed $data Дополнительные данные (для AJAX-запросов)
     * @param string $redirectUrl URL для перенаправления (для не-AJAX запросов)
     */
    public static function handleSuccess($message, $data = null, $redirectUrl = null) {
        if (self::isAjaxRequest()) {
            $response = [
                'success' => true,
                'message' => $message
            ];
            
            if ($data !== null) {
                $response['data'] = $data;
            }
            
            if ($redirectUrl) {
                $response['redirect'] = $redirectUrl;
            }
            
            self::sendJsonResponse($response);
        } else {
            if ($redirectUrl) {
                $_SESSION['success'] = $message;
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                echo '<div class="alert alert-success">' . $message . '</div>';
            }
        }
    }
}
