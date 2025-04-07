<?php
/**
 * Базовый класс для API клиентов социальных сетей с поддержкой прокси
 */
class BaseApiClient {
    /**
     * @var array Данные прокси
     */
    protected $proxy = null;
    
    /**
     * @var Logger Экземпляр класса для логирования
     */
    protected $logger;
    
    /**
     * Конструктор класса
     * 
     * @param Logger $logger Экземпляр класса для логирования (опционально)
     */
    public function __construct($logger = null) {
        $this->logger = $logger ?: new Logger(get_class($this));
    }
    
    /**
     * Установка прокси для API клиента
     * 
     * @param array $proxy Данные прокси
     * @return self
     */
    public function setProxy($proxy) {
        $this->proxy = $proxy;
        $this->logger->debug("Proxy set: {$proxy['ip']}:{$proxy['port']}");
        return $this;
    }
    
    /**
     * Настройка cURL с использованием прокси
     * 
     * @param resource $ch Ресурс cURL
     * @return resource Настроенный ресурс cURL
     */
    protected function setupCurlWithProxy($ch) {
        if (!$this->proxy) {
            return $ch;
        }
        
        // Формируем строку прокси для cURL
        $proxyString = "{$this->proxy['protocol']}://{$this->proxy['ip']}:{$this->proxy['port']}";
        
        // Настраиваем cURL
        curl_setopt($ch, CURLOPT_PROXY, $proxyString);
        
        // Если есть аутентификация, добавляем ее
        if (!empty($this->proxy['username']) && !empty($this->proxy['password'])) {
            $proxyAuth = "{$this->proxy['username']}:{$this->proxy['password']}";
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
        }
        
        $this->logger->debug("cURL configured with proxy: $proxyString");
        return $ch;
    }
}
