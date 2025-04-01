<?php
/**
 * Класс для парсинга контента из различных источников
 */
class ContentParser {
    /**
     * Метод для парсинга RSS-ленты
     * 
     * @param string $url URL RSS-ленты
     * @param int $limit Максимальное количество записей
     * @return array Массив записей из RSS-ленты
     */
    public function parseRss($url, $limit = 10) {
        try {
            // Загрузка RSS-ленты
            $rssContent = file_get_contents($url);
            
            if (!$rssContent) {
                throw new Exception("Failed to load RSS feed from URL: " . $url);
            }
            
            // Создание объекта SimpleXML
            $rss = simplexml_load_string($rssContent);
            
            if (!$rss) {
                throw new Exception("Failed to parse RSS feed as XML");
            }
            
            $items = [];
            $count = 0;
            
            // Обработка элементов RSS
            foreach ($rss->channel->item as $item) {
                if ($count >= $limit) {
                    break;
                }
                
                // Извлечение данных из элемента
                $pubDate = isset($item->pubDate) ? (string)$item->pubDate : '';
                $timestamp = strtotime($pubDate);
                
                $items[] = [
                    'title' => (string)$item->title,
                    'description' => (string)$item->description,
                    'link' => (string)$item->link,
                    'pubDate' => $pubDate,
                    'timestamp' => $timestamp,
                    'source' => $url,
                    'type' => 'rss'
                ];
                
                $count++;
            }
            
            return $items;
        } catch (Exception $e) {
            error_log("RSS parsing error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Метод для парсинга блога
     * 
     * @param string $url URL блога
     * @param array $selectors Селекторы для извлечения данных
     * @param int $limit Максимальное количество записей
     * @return array Массив записей из блога
     */
    public function parseBlog($url, $selectors = [], $limit = 10) {
        try {
            // Загрузка страницы блога
            $html = file_get_contents($url);
            
            if (!$html) {
                throw new Exception("Failed to load blog page from URL: " . $url);
            }
            
            // Создание DOM-документа
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            // Установка селекторов по умолчанию, если не указаны
            $defaultSelectors = [
                'container' => '//article',
                'title' => './/h1|.//h2',
                'content' => './/div[contains(@class, "content")]|.//div[contains(@class, "entry")]',
                'link' => './/a[@class="read-more"]|.//a[contains(@class, "more-link")]',
                'date' => './/time|.//span[contains(@class, "date")]'
            ];
            
            $selectors = array_merge($defaultSelectors, $selectors);
            
            // Поиск контейнеров с записями
            $containers = $xpath->query($selectors['container']);
            
            $items = [];
            $count = 0;
            
            // Обработка найденных записей
            foreach ($containers as $container) {
                if ($count >= $limit) {
                    break;
                }
                
                // Извлечение данных из записи
                $titleNode = $xpath->query($selectors['title'], $container)->item(0);
                $contentNode = $xpath->query($selectors['content'], $container)->item(0);
                $linkNode = $xpath->query($selectors['link'], $container)->item(0);
                $dateNode = $xpath->query($selectors['date'], $container)->item(0);
                
                $title = $titleNode ? trim($titleNode->textContent) : '';
                $content = $contentNode ? trim($contentNode->textContent) : '';
                $link = $linkNode ? $linkNode->getAttribute('href') : '';
                $date = $dateNode ? trim($dateNode->textContent) : '';
                
                // Преобразование относительных ссылок в абсолютные
                if ($link && strpos($link, 'http') !== 0) {
                    $link = $this->makeAbsoluteUrl($url, $link);
                }
                
                $timestamp = strtotime($date);
                
                $items[] = [
                    'title' => $title,
                    'description' => $content,
                    'link' => $link,
                    'pubDate' => $date,
                    'timestamp' => $timestamp,
                    'source' => $url,
                    'type' => 'blog'
                ];
                
                $count++;
            }
            
            return $items;
        } catch (Exception $e) {
            error_log("Blog parsing error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Метод для парсинга социальных сетей
     * 
     * @param string $url URL страницы социальной сети
     * @param string $type Тип социальной сети (twitter, facebook, instagram, etc.)
     * @param int $limit Максимальное количество записей
     * @return array Массив записей из социальной сети
     */
    public function parseSocialMedia($url, $type, $limit = 10) {
        try {
            switch (strtolower($type)) {
                case 'twitter':
                    return $this->parseTwitter($url, $limit);
                case 'facebook':
                    return $this->parseFacebook($url, $limit);
                case 'instagram':
                    return $this->parseInstagram($url, $limit);
                case 'linkedin':
                    return $this->parseLinkedIn($url, $limit);
                default:
                    throw new Exception("Unsupported social media type: " . $type);
            }
        } catch (Exception $e) {
            error_log("Social media parsing error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Метод для парсинга Twitter
     * 
     * @param string $url URL страницы Twitter
     * @param int $limit Максимальное количество записей
     * @return array Массив записей из Twitter
     */
    private function parseTwitter($url, $limit = 10) {
        // Извлечение имени пользователя из URL
        $username = '';
        if (preg_match('/twitter\.com\/([^\/\?]+)/', $url, $matches)) {
            $username = $matches[1];
        } else {
            throw new Exception("Invalid Twitter URL: " . $url);
        }
        
        // Здесь должен быть код для использования Twitter API
        // В данном примере используется заглушка
        
        $items = [];
        
        // Заглушка для демонстрации структуры данных
        for ($i = 0; $i < $limit; $i++) {
            $items[] = [
                'title' => 'Tweet from ' . $username,
                'description' => 'This is a placeholder for a tweet content',
                'link' => $url,
                'pubDate' => date('r', time() - $i * 3600),
                'timestamp' => time() - $i * 3600,
                'source' => $url,
                'type' => 'twitter'
            ];
        }
        
        return $items;
    }
    
    /**
     * Метод для парсинга Facebook
     * 
     * @param string $url URL страницы Facebook
     * @param int $limit Максимальное количество записей
     * @return array Массив записей из Facebook
     */
    private function parseFacebook($url, $limit = 10) {
        // Здесь должен быть код для использования Facebook API
        // В данном примере используется заглушка
        
        $items = [];
        
        // Заглушка для демонстрации структуры данных
        for ($i = 0; $i < $limit; $i++) {
            $items[] = [
                'title' => 'Facebook post',
                'description' => 'This is a placeholder for a Facebook post content',
                'link' => $url,
                'pubDate' => date('r', time() - $i * 3600),
                'timestamp' => time() - $i * 3600,
                'source' => $url,
                'type' => 'facebook'
            ];
        }
        
        return $items;
    }
    
    /**
     * Метод для парсинга Instagram
     * 
     * @param string $url URL страницы Instagram
     * @param int $limit Максимальное количество записей
     * @return array Массив записей из Instagram
     */
    private function parseInstagram($url, $limit = 10) {
        // Здесь должен быть код для использования Instagram API
        // В данном примере используется заглушка
        
        $items = [];
        
        // Заглушка для демонстрации структуры данных
        for ($i = 0; $i < $limit; $i++) {
            $items[] = [
                'title' => 'Instagram post',
                'description' => 'This is a placeholder for an Instagram post content',
                'link' => $url,
                'pubDate' => date('r', time() - $i * 3600),
                'timestamp' => time() - $i * 3600,
                'source' => $url,
                'type' => 'instagram'
            ];
        }
        
        return $items;
    }
    
    /**
     * Метод для парсинга LinkedIn
     * 
     * @param string $url URL страницы LinkedIn
     * @param int $limit Максимальное количество записей
     * @return array Массив записей из LinkedIn
     */
    private function parseLinkedIn($url, $limit = 10) {
        // Здесь должен быть код для использования LinkedIn API
        // В данном примере используется заглушка
        
        $items = [];
        
        // Заглушка для демонстрации структуры данных
        for ($i = 0; $i < $limit; $i++) {
            $items[] = [
                'title' => 'LinkedIn post',
                'description' => 'This is a placeholder for a LinkedIn post content',
                'link' => $url,
                'pubDate' => date('r', time() - $i * 3600),
                'timestamp' => time() - $i * 3600,
                'source' => $url,
                'type' => 'linkedin'
            ];
        }
        
        return $items;
    }
    
    /**
     * Метод для преобразования относительного URL в абсолютный
     * 
     * @param string $baseUrl Базовый URL
     * @param string $relativeUrl Относительный URL
     * @return string Абсолютный URL
     */
    private function makeAbsoluteUrl($baseUrl, $relativeUrl) {
        $parsedBase = parse_url($baseUrl);
        
        // Если URL уже абсолютный, возвращаем его
        if (strpos($relativeUrl, 'http') === 0) {
            return $relativeUrl;
        }
        
        // Если URL начинается с //, добавляем протокол
        if (strpos($relativeUrl, '//') === 0) {
            return $parsedBase['scheme'] . ':' . $relativeUrl;
        }
        
        // Если URL начинается с /, добавляем домен
        if (strpos($relativeUrl, '/') === 0) {
            return $parsedBase['scheme'] . '://' . $parsedBase['host'] . $relativeUrl;
        }
        
        // В остальных случаях добавляем базовый URL
        $path = isset($parsedBase['path']) ? $parsedBase['path'] : '';
        $path = substr($path, 0, strrpos($path, '/') + 1);
        
        return $parsedBase['scheme'] . '://' . $parsedBase['host'] . $path . $relativeUrl;
    }
}
