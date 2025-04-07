<?php
/**
 * Класс для парсинга контента из различных источников
 * с акцентом на RSS и новостные сайты
 */
class ContentParser {
    /**
     * Метод для парсинга RSS-ленты
     * 
     * @param string $url URL RSS-ленты
     * @param int $limit Максимальное количество записей
     * @param bool $fetchFullContent Получать полный контент статей
     * @param array|null $proxyConfig Конфигурация прокси
     * @return array Массив записей из RSS-ленты
     */
    public function parseRss($url, $limit = 10, $fetchFullContent = false, $proxyConfig = null) {
        try {
            // Расширенное логирование входящих параметров
            error_log("ParseRSS Input: " . json_encode([
                'url' => $url,
                'proxyConfig' => $proxyConfig ? [
                    'host' => $proxyConfig['host'],
                    'port' => $proxyConfig['port'],
                    'protocol' => $proxyConfig['protocol'] ?? 'не указан'
                ] : 'No Proxy'
            ]));

            // Проверка URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception("Invalid RSS feed URL: " . $url);
            }
            
            // Подготовка базовых опций cURL
            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/rss+xml,text/xml,*/*',
                    'Accept-Language: en-US,en;q=0.9',
                    'Referer: ' . $url,
                    'Cache-Control: no-cache',
                    'Pragma: no-cache'
                ]
            ];
            
            // Флаг использования прокси
            $isProxyUsed = false;
            $proxyTestResult = null;
            
            // Проверка и настройка прокси
            if ($proxyConfig && is_array($proxyConfig)) {
                // Проверка обязательных параметров прокси
                if (!empty($proxyConfig['host']) && !empty($proxyConfig['port'])) {
                    $proxyString = $proxyConfig['host'] . ':' . $proxyConfig['port'];
                    
                    // Тест прокси перед использованием
                    try {
                        $proxyTestCh = curl_init('https://api.ipify.org');
                        curl_setopt_array($proxyTestCh, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_PROXY => $proxyString,
                            CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
                            CURLOPT_TIMEOUT => 10
                        ]);
                        
                        // Аутентификация, если указана
                        if (!empty($proxyConfig['username']) && !empty($proxyConfig['password'])) {
                            curl_setopt($proxyTestCh, CURLOPT_PROXYUSERPWD, 
                                $proxyConfig['username'] . ':' . $proxyConfig['password']
                            );
                        }
                        
                        $proxyTestResult = curl_exec($proxyTestCh);
                        $proxyTestError = curl_error($proxyTestCh);
                        $proxyTestInfo = curl_getinfo($proxyTestCh);
                        
                        curl_close($proxyTestCh);
                        
                        // Логирование результатов теста прокси
                        error_log("PROXY TEST DETAILS: " . json_encode([
                            'IP' => $proxyTestResult,
                            'Error' => $proxyTestError,
                            'HTTP Code' => $proxyTestInfo['http_code']
                        ]));
                        
                        // Установка прокси, если тест успешен
                        if (!$proxyTestError && $proxyTestInfo['http_code'] == 200) {
                            $curlOptions[CURLOPT_PROXY] = $proxyString;
                            $isProxyUsed = true;
                            
                            // Аутентификация
                            if (!empty($proxyConfig['username']) && !empty($proxyConfig['password'])) {
                                $curlOptions[CURLOPT_PROXYUSERPWD] = 
                                    $proxyConfig['username'] . ':' . $proxyConfig['password'];
                            }
                            
                            // Определение типа прокси
                            switch (strtolower($proxyConfig['protocol'] ?? 'socks5')) {
                                case 'http':
                                    $curlOptions[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
                                    break;
                                case 'https':
                                    $curlOptions[CURLOPT_PROXYTYPE] = CURLPROXY_HTTPS;
                                    break;
                                case 'socks4':
                                    $curlOptions[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS4;
                                    break;
                                case 'socks5':
                                default:
                                    $curlOptions[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
                            }
                        } else {
                            error_log("PROXY TEST FAILED: " . $proxyTestError);
                        }
                    } catch (Exception $e) {
                        error_log("PROXY TEST EXCEPTION: " . $e->getMessage());
                    }
                }
            }
            
            // Инициализация cURL
            $ch = curl_init($url);
            curl_setopt_array($ch, $curlOptions);
            
            // Выполнение запроса
            $rssContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $requestInfo = curl_getinfo($ch);
            
            curl_close($ch);
            
            // Логирование результатов
            $debugContext = [
                'URL' => $url,
                'HTTP Code' => $httpCode,
                'Proxy Used' => $isProxyUsed ? 
                    ($proxyConfig['host'] . ':' . $proxyConfig['port']) : 
                    'No Proxy',
                'Proxy Configuration' => $proxyConfig ? json_encode($proxyConfig) : 'N/A',
                'Proxy Test Result' => $proxyTestResult,
                'Total Time' => $requestInfo['total_time'] ?? 'N/A',
                'Primary IP' => $requestInfo['primary_ip'] ?? 'N/A',
                'Error' => $error
            ];
            
            Logger::debug("RSS Parsing Detailed Results", 'parsing', $debugContext);
            
            // Проверка результата
            if ($error) {
                throw new Exception("cURL Proxy Error: " . $error);
            }
            
            if ($httpCode !== 200) {
                throw new Exception("HTTP Error (via " . 
                    ($isProxyUsed ? "Proxy {$proxyConfig['host']}:{$proxyConfig['port']}" : "Direct") . 
                    "): " . $httpCode
                );
            }
            
            if (!$rssContent) {
                throw new Exception("No content received from RSS feed");
            }
            
            // Подготовка к парсингу XML
            libxml_use_internal_errors(true);
            $rss = simplexml_load_string($rssContent);
            
            // Проверка ошибок XML
            if ($rss === false) {
                $errors = libxml_get_errors();
                $errorMsg = "XML Parsing Errors: ";
                foreach ($errors as $error) {
                    $errorMsg .= "Line {$error->line}: {$error->message}; ";
                }
                libxml_clear_errors();
                throw new Exception($errorMsg);
            }
            
            // Определение типа RSS
            $items = [];
            $count = 0;
            
            // Обработка различных форматов RSS
            if (isset($rss->channel) && isset($rss->channel->item)) {
                // RSS 2.0
                foreach ($rss->channel->item as $item) {
                    if ($count >= $limit) break;
                    
                    $parsedItem = $this->extractItemData($item, $url, $fetchFullContent);
                    if ($parsedItem) {
                        $items[] = $parsedItem;
                        $count++;
                    }
                }
            } elseif (isset($rss->entry)) {
                // Atom
                foreach ($rss->entry as $entry) {
                    if ($count >= $limit) break;
                    
                    $parsedItem = $this->extractAtomEntry($entry, $url, $fetchFullContent);
                    if ($parsedItem) {
                        $items[] = $parsedItem;
                        $count++;
                    }
                }
            }
            
            // Сортировка по дате (от новых к старым)
            usort($items, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            return array_slice($items, 0, $limit);
        } catch (Exception $e) {
            error_log("FINAL PARSING ERROR: " . $e->getMessage());
            Logger::error("RSS Parsing Error for {$url}: " . $e->getMessage(), 'parsing');
            
            return [
                'error' => $e->getMessage(),
                'url' => $url
            ];
        }
    }

    /**
     * Получение внешнего IP-адреса
     * 
     * @return string IP-адрес
     */
    private function getExternalIp() {
        try {
            $ch = curl_init('https://api.ipify.org');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10
            ]);
            $ip = curl_exec($ch);
            curl_close($ch);
            
            return $ip ?: 'Unable to determine';
        } catch (Exception $e) {
            return 'Error getting IP: ' . $e->getMessage();
        }
    }
    
    /**
     * Обработка RSS 2.0
     */
    private function processRss20($rss, $limit, $sourceUrl, $fetchFullContent) {
        $items = [];
        $count = 0;
        
        foreach ($rss->channel->item as $item) {
            if ($count >= $limit) break;
            
            $itemData = $this->extractItemData($item, $sourceUrl, $fetchFullContent);
            if ($itemData) {
                $items[] = $itemData;
                $count++;
            }
        }
        
        return $items;
    }
    
    /**
     * Обработка Atom
     */
    private function processAtom($rss, $limit, $sourceUrl, $fetchFullContent) {
        $items = [];
        $count = 0;
        
        foreach ($rss->entry as $entry) {
            if ($count >= $limit) break;
            
            // Извлечение данных из элемента Atom
            $title = isset($entry->title) ? (string)$entry->title : '';
            
            // В Atom link - это атрибут href
            $link = '';
            if (isset($entry->link)) {
                foreach ($entry->link as $linkElement) {
                    $attributes = $linkElement->attributes();
                    if (isset($attributes['rel']) && $attributes['rel'] == 'alternate') {
                        $link = (string)$attributes['href'];
                        break;
                    }
                    // Если rel не указан или это первая ссылка
                    if (empty($link) && isset($attributes['href'])) {
                        $link = (string)$attributes['href'];
                    }
                }
            }
            
            // В Atom вместо description используется content или summary
            $description = '';
            if (isset($entry->content)) {
                $description = (string)$entry->content;
            } elseif (isset($entry->summary)) {
                $description = (string)$entry->summary;
            }
            
            // В Atom вместо pubDate используется published или updated
            $pubDate = '';
            $timestamp = time();
            if (isset($entry->published)) {
                $pubDate = (string)$entry->published;
                $timestamp = strtotime($pubDate);
            } elseif (isset($entry->updated)) {
                $pubDate = (string)$entry->updated;
                $timestamp = strtotime($pubDate);
            }
            
            // Автор
            $author = '';
            if (isset($entry->author->name)) {
                $author = (string)$entry->author->name;
            }
            
            // Получение полного контента, если требуется
            if ($fetchFullContent && !empty($link)) {
                $fullContent = $this->fetchArticleContent($link);
                if (!empty($fullContent)) {
                    $description = $fullContent;
                }
            }
            
            // Преобразование относительных ссылок в абсолютные
            if ($link && strpos($link, 'http') !== 0) {
                $link = $this->makeAbsoluteUrl($sourceUrl, $link);
            }
            
            $items[] = [
                'title' => $title,
                'description' => $description,
                'link' => $link,
                'pubDate' => $pubDate,
                'timestamp' => $timestamp,
                'author' => $author,
                'source' => $sourceUrl,
                'type' => 'atom'
            ];
            
            $count++;
        }
        
        return $items;
    }
    
    /**
     * Обработка RSS 1.0
     */
    private function processRss10($rss, $limit, $sourceUrl, $fetchFullContent) {
        $items = [];
        $count = 0;
        
        $itemElements = isset($rss->item) ? $rss->item : $rss->channel->item;
        
        foreach ($itemElements as $item) {
            if ($count >= $limit) break;
            
            $itemData = $this->extractItemData($item, $sourceUrl, $fetchFullContent);
            if ($itemData) {
                $items[] = $itemData;
                $count++;
            }
        }
        
        return $items;
    }
    
    /**
     * Извлечение данных из элемента RSS
     */
    private function extractItemData($item, $sourceUrl, $fetchFullContent) {
        // Извлечение базовых данных
        $title = isset($item->title) ? trim((string)$item->title) : '';
        $description = isset($item->description) ? trim((string)$item->description) : '';
        $link = isset($item->link) ? trim((string)$item->link) : '';
        
        // Обработка даты публикации
        $pubDate = isset($item->pubDate) ? (string)$item->pubDate : 
                 (isset($item->date) ? (string)$item->date : '');
        $timestamp = $pubDate ? strtotime($pubDate) : time();
        
        // Если не удалось распарсить дату
        if ($timestamp === false) {
            $timestamp = time();
        }
        
        // Автор
        $author = isset($item->author) ? (string)$item->author : 
                (isset($item->creator) ? (string)$item->creator : '');
        
        // Получение полного контента, если требуется
        if ($fetchFullContent && !empty($link)) {
            $fullContent = $this->fetchArticleContent($link);
            if (!empty($fullContent)) {
                $description = $fullContent;
            }
        }
        
        // Преобразование относительных ссылок в абсолютные
        if ($link && strpos($link, 'http') !== 0) {
            $link = $this->makeAbsoluteUrl($sourceUrl, $link);
        }
        
        return [
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'pubDate' => $pubDate,
            'timestamp' => $timestamp,
            'author' => $author,
            'source' => $sourceUrl,
            'type' => 'rss'
        ];
    }
    
    /**
     * Получение полного контента статьи по URL
     */
    private function fetchArticleContent($url) {
        try {
            // Используем cURL для большей гибкости
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ]);
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode != 200 || !$html) {
                return '';
            }
            
            // Используем DOM для извлечения контента
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            // Список приоритетных селекторов для содержимого статьи
            // Сначала ищем по наиболее распространенным классам и ID
            $contentSelectors = [
                "//article",
                "//div[contains(@class, 'article-content')]",
                "//div[contains(@class, 'post-content')]",
                "//div[contains(@class, 'entry-content')]",
                "//div[@id='content']",
                "//div[contains(@class, 'content')]",
                "//div[contains(@class, 'main-content')]",
                "//main",
                "//div[@role='main']"
            ];
            
            // Ищем контент по приоритетным селекторам
            $content = '';
            foreach ($contentSelectors as $selector) {
                $contentNodes = $xpath->query($selector);
                if ($contentNodes && $contentNodes->length > 0) {
                    $contentNode = $contentNodes->item(0);
                    
                    // Удаляем ненужные элементы (комментарии, навигацию, рекламу и т.д.)
                    $this->removeUnwantedElements($contentNode, $xpath);
                    
                    // Получаем HTML содержимое
                    $content = $dom->saveHTML($contentNode);
                    break;
                }
            }
            
            // Если не удалось найти контент по селекторам, берем содержимое body
            if (empty($content)) {
                $bodyNodes = $xpath->query("//body");
                if ($bodyNodes && $bodyNodes->length > 0) {
                    $contentNode = $bodyNodes->item(0);
                    $this->removeUnwantedElements($contentNode, $xpath);
                    $content = $dom->saveHTML($contentNode);
                }
            }
            
            // Очистка контента от лишних HTML-тегов
            $content = strip_tags($content, '<p><br><h1><h2><h3><h4><h5><ul><ol><li><blockquote><img><figure><figcaption>');
            
            return trim($content);
        } catch (Exception $e) {
            error_log("Error fetching article content: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Удаление ненужных элементов из DOM-узла
     */
    private function removeUnwantedElements($node, $xpath) {
        // Селекторы элементов, которые нужно удалить
        $removeSelectors = [
            ".//div[contains(@class, 'comments')]",
            ".//div[contains(@class, 'comment')]",
            ".//div[contains(@class, 'sidebar')]",
            ".//aside",
            ".//nav",
            ".//header",
            ".//footer",
            ".//div[contains(@class, 'related')]",
            ".//div[contains(@class, 'share')]",
            ".//div[contains(@class, 'social')]",
            ".//div[contains(@class, 'advertisement')]",
            ".//div[contains(@class, 'ad-')]",
            ".//div[contains(@class, 'widget')]",
            ".//div[contains(@id, 'widget')]",
            ".//script",
            ".//style",
            ".//iframe"
        ];
        
        // Удаляем элементы
        foreach ($removeSelectors as $selector) {
            $elements = $xpath->query($selector, $node);
            if ($elements) {
                foreach ($elements as $element) {
                    if ($element->parentNode) {
                        $element->parentNode->removeChild($element);
                    }
                }
            }
        }
    }
    
    /**
     * Метод для парсинга новостного сайта
     * 
     * @param string $url URL сайта
     * @param array $selectors Селекторы для извлечения данных
     * @param int $limit Максимальное количество записей
     * @param bool $fetchFullContent Получать полный контент статей
     * @return array Массив записей из блога
     */
    public function parseBlog($url, $selectors = [], $limit = 10, $fetchFullContent = false) {
        try {
            // Проверяем URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception("Invalid blog URL: " . $url);
            }
            
            // Используем cURL для получения HTML с расширенными заголовками
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 20,
                // Более реалистичный User-Agent
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
                // Добавляем дополнительные заголовки для имитации реального браузера
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                    'Cache-Control: max-age=0',
                    'Referer: https://www.google.com/',
                    'DNT: 1'
                ],
                // Включаем поддержку cookies
                CURLOPT_COOKIEJAR => '/tmp/cookies.txt',
                CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
                // Отключаем проверку SSL для решения проблем с сертификатами
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            if (!empty($proxyConfig)) {
                error_log("Setting up proxy: $proxyConfig");
                curl_setopt($ch, CURLOPT_PROXY, $proxyConfig);
                
                // Если прокси требует аутентификации
                if (!empty($proxyAuth)) {
                    error_log("Using proxy authentication");
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
                }
                
                // Добавим запрос к сервису, показывающему IP для проверки
                $checkCh = curl_init("https://api.ipify.org");
                curl_setopt_array($checkCh, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_PROXY => $proxyConfig,
                    CURLOPT_PROXYUSERPWD => $proxyAuth,
                    CURLOPT_TIMEOUT => 10
                ]);
                $ip = curl_exec($checkCh);
                error_log("Current IP address through proxy: " . ($ip ?: "Failed to determine"));
                curl_close($checkCh);
            }
            
            // Попытка найти и использовать RSS-ленту
            $rssUrl = $this->findRssFeed($url);
            if ($rssUrl) {
                error_log("Found RSS feed at $rssUrl, using it instead of direct parsing");
                return $this->parseRss($rssUrl, $limit, $fetchFullContent);
            }
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("cURL error: " . $error);
            }
            
            if ($httpCode != 200 || !$html) {
                // Если получили 403, проверяем наличие RSS
                if ($httpCode == 403) {
                    $possibleRssUrls = [
                        $url . "/rss",
                        $url . "/feed",
                        $url . "/rss.xml",
                        $url . "/atom.xml",
                        $url . "/feed/rss",
                        $url . "/index.xml",
                        str_replace('www.', '', $url) . "/rss"
                    ];
                    
                    foreach ($possibleRssUrls as $possibleRssUrl) {
                        if ($this->isValidRssFeed($possibleRssUrl)) {
                            error_log("Direct access blocked but found RSS at $possibleRssUrl");
                            return $this->parseRss($possibleRssUrl, $limit, $fetchFullContent);
                        }
                    }
                }
                
                throw new Exception("Failed to load blog page from URL: " . $url . " (HTTP code: $httpCode)");
            }
            
            // Создание DOM-документа
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            // Поиск RSS-ссылки на странице и использование её, если нашли
            $rssLinks = $xpath->query("//link[@rel='alternate' and (@type='application/rss+xml' or @type='application/atom+xml')]");
            if ($rssLinks->length > 0) {
                $rssUrl = $rssLinks->item(0)->getAttribute('href');
                if (strpos($rssUrl, 'http') !== 0) {
                    $rssUrl = $this->makeAbsoluteUrl($url, $rssUrl);
                }
                
                error_log("Found RSS link in HTML: $rssUrl");
                return $this->parseRss($rssUrl, $limit, $fetchFullContent);
            }
            
            // Установка селекторов по умолчанию, если не указаны
            $defaultSelectors = [
                'container' => "//article | //div[contains(@class, 'post')] | //div[contains(@class, 'entry')] | //div[contains(@class, 'article')] | //div[contains(@class, 'card-news')] | //div[contains(@class, 'news-item')]",
                'title' => ".//h1 | .//h2 | .//h3[contains(@class, 'title')] | .//h4[contains(@class, 'title')] | .//h5[contains(@class, 'title')]",
                'content' => ".//div[contains(@class, 'content')] | .//div[contains(@class, 'body')] | .//div[contains(@class, 'text')] | .//div[contains(@class, 'description')] | .//p[contains(@class, 'excerpt')]",
                'link' => ".//a[contains(@class, 'read-more')] | .//a[contains(@class, 'more-link')] | .//h2/a | .//h1/a | .//h3/a | .//h4/a | .//h5/a | .//a[contains(@class, 'title')]",
                'date' => ".//time | .//span[contains(@class, 'date')] | .//div[contains(@class, 'date')] | .//p[contains(@class, 'date')] | .//meta[@itemprop='datePublished'] | .//span[contains(@class, 'published')]"
            ];
            
            $selectors = array_merge($defaultSelectors, $selectors);
            
            // Поиск контейнеров с записями
            $containers = $xpath->query($selectors['container']);
            
            // Если контейнеры не найдены, попробуем найти по расширенному списку селекторов
            if ($containers->length == 0) {
                $alternativeSelectors = [
                    "//div[contains(@class, 'post-list')]//div[contains(@class, 'item')]",
                    "//div[contains(@class, 'news-list')]//div[contains(@class, 'item')]",
                    "//ul[contains(@class, 'news-list')]/li",
                    "//div[contains(@class, 'news-grid')]//div[contains(@class, 'item')]",
                    "//div[@id='content']//div[contains(@class, 'item')]",
                    "//div[contains(@class, 'latest-news')]//div[contains(@class, 'item')]",
                    "//div[contains(@class, 'listing')]//div[contains(@class, 'item')]",
                    "//div[contains(@class, 'blog')]/div[contains(@class, 'row')]/div",
                    "//div[contains(@class, 'article-list')]/div",
                    "//div[contains(@class, 'card')]",
                    "//div[contains(@class, 'block')]//div[contains(@class, 'item')]",
                    "//main//article",
                    "//div[@role='main']//article"
                ];
                
                foreach ($alternativeSelectors as $selector) {
                    $containers = $xpath->query($selector);
                    if ($containers->length > 0) {
                        error_log("Found items using alternative selector: $selector");
                        break;
                    }
                }
            }
            
            $items = [];
            $count = 0;
            
            // Если все равно не нашли контейнеры, попробуем применить эвристический подход
            if ($containers->length == 0) {
                error_log("No containers found with standard selectors, using heuristic approach");
                
                // Ищем все заголовки с ссылками
                $titleLinks = $xpath->query("//h1/a | //h2/a | //h3/a | //h4/a | //h5/a");
                
                foreach ($titleLinks as $titleLink) {
                    if ($count >= $limit) {
                        break;
                    }
                    
                    $title = trim($titleLink->textContent);
                    $link = $titleLink->getAttribute('href');
                    
                    if (empty($title) || empty($link)) {
                        continue;
                    }
                    
                    // Преобразование относительных ссылок в абсолютные
                    if ($link && strpos($link, 'http') !== 0) {
                        $link = $this->makeAbsoluteUrl($url, $link);
                    }
                    
                    // Получение контента и даты из полной статьи, если включен режим fetchFullContent
                    $content = '';
                    $timestamp = time();
                    
                    if ($fetchFullContent && !empty($link)) {
                        $fullContent = $this->fetchArticleContent($link);
                        if (!empty($fullContent)) {
                            $content = $fullContent;
                        }
                    }
                    
                    $items[] = [
                        'title' => $title,
                        'description' => $content,
                        'link' => $link,
                        'pubDate' => date('r', $timestamp),
                        'timestamp' => $timestamp,
                        'source' => $url,
                        'type' => 'blog'
                    ];
                    
                    $count++;
                }
            } else {
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
                    
                    // Если не нашли ссылку через селектор, но нашли заголовок, который является ссылкой
                    if (empty($link) && $titleNode && $titleNode->parentNode && $titleNode->parentNode->nodeName === 'a') {
                        $link = $titleNode->parentNode->getAttribute('href');
                    } else if (empty($link) && $titleNode) {
                        // Поиск ближайшей ссылки, связанной с заголовком
                        $linkNodes = $xpath->query(".//a", $container);
                        if ($linkNodes->length > 0) {
                            $link = $linkNodes->item(0)->getAttribute('href');
                        }
                    }
                    
                    // Если до сих пор не нашли ссылку, ищем любую ссылку в контейнере
                    if (empty($link)) {
                        $allLinks = $xpath->query(".//a", $container);
                        if ($allLinks->length > 0) {
                            $link = $allLinks->item(0)->getAttribute('href');
                        }
                    }
                    
                    // Пропускаем запись, если не нашли заголовок или ссылку
                    if (empty($title) || empty($link)) {
                        continue;
                    }
                    
                    // Преобразование относительных ссылок в абсолютные
                    if ($link && strpos($link, 'http') !== 0) {
                        $link = $this->makeAbsoluteUrl($url, $link);
                    }
                    
                    // Попытка извлечь дату из текста или из атрибутов
                    $timestamp = time();
                    if (!empty($date)) {
                        $parsedTimestamp = strtotime($date);
                        if ($parsedTimestamp !== false) {
                            $timestamp = $parsedTimestamp;
                        } else {
                            // Попытка извлечь дату из формата "DD.MM.YYYY" или подобного
                            preg_match('/(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})/', $date, $matches);
                            if (count($matches) > 3) {
                                $day = $matches[1];
                                $month = $matches[2];
                                $year = $matches[3];
                                if (strlen($year) == 2) {
                                    $year = '20' . $year;
                                }
                                $timestamp = strtotime("$year-$month-$day");
                            }
                        }
                    } else if ($dateNode && $dateNode->hasAttribute('datetime')) {
                        $datetime = $dateNode->getAttribute('datetime');
                        $parsedTimestamp = strtotime($datetime);
                        if ($parsedTimestamp !== false) {
                            $timestamp = $parsedTimestamp;
                        }
                    }
                    
                    // Извлечение даты из схемы микроданных
                    if ($dateNode == null) {
                        $metaDate = $xpath->query(".//meta[@itemprop='datePublished']", $container);
                        if ($metaDate->length > 0) {
                            $dateValue = $metaDate->item(0)->getAttribute('content');
                            $parsedTimestamp = strtotime($dateValue);
                            if ($parsedTimestamp !== false) {
                                $timestamp = $parsedTimestamp;
                            }
                        }
                    }
                    
                    // Получение полного контента, если требуется
                    if ($fetchFullContent && !empty($link)) {
                        $fullContent = $this->fetchArticleContent($link);
                        if (!empty($fullContent)) {
                            $content = $fullContent;
                        }
                    }
                    
                    $items[] = [
                        'title' => $title,
                        'description' => $content,
                        'link' => $link,
                        'pubDate' => date('r', $timestamp),
                        'timestamp' => $timestamp,
                        'source' => $url,
                        'type' => 'blog'
                    ];
                    
                    $count++;
                }
            }
            
            // Если не нашли ни одной записи, попробуем использовать стандартные пути RSS
            if (empty($items)) {
                $possibleRssUrls = [
                    $url . "/rss",
                    $url . "/feed",
                    $url . "/rss.xml",
                    $url . "/atom.xml",
                    $url . "/feed/rss",
                    $url . "/index.xml",
                    str_replace('www.', '', $url) . "/rss"
                ];
                
                foreach ($possibleRssUrls as $possibleRssUrl) {
                    if ($this->isValidRssFeed($possibleRssUrl)) {
                        error_log("No items found in HTML, but found RSS at $possibleRssUrl");
                        return $this->parseRss($possibleRssUrl, $limit, $fetchFullContent);
                    }
                }
                
                error_log("No items found and no RSS feed available for $url");
            }
            
            // Сортировка по времени (от новых к старым)
            usort($items, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            
            return $items;
        } catch (Exception $e) {
            error_log("Blog parsing error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Проверяет, является ли URL действительной RSS-лентой
     * 
     * @param string $url URL для проверки
     * @return bool Является ли URL действительной RSS-лентой
     */
    private function isValidRssFeed($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
            CURLOPT_NOBODY => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if ($httpCode != 200 || !$content) {
            return false;
        }
        
        // Проверка по Content-Type
        if (strpos($contentType, 'application/rss+xml') !== false || 
            strpos($contentType, 'application/atom+xml') !== false || 
            strpos($contentType, 'application/xml') !== false || 
            strpos($contentType, 'text/xml') !== false) {
            return true;
        }
        
        // Проверка по содержимому
        if (strpos($content, '<rss') !== false || 
            strpos($content, '<feed') !== false || 
            (strpos($content, '<channel') !== false && strpos($content, '<item') !== false)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Находит RSS-ленту для указанного URL
     * 
     * @param string $url URL сайта
     * @return string|false URL RSS-ленты или false если не найдено
     */
    private function findRssFeed($url) {
        $possibleRssUrls = [
            $url . "/rss",
            $url . "/feed",
            $url . "/rss.xml",
            $url . "/atom.xml",
            $url . "/feed/rss",
            $url . "/index.xml",
            rtrim($url, '/') . ".xml",
            str_replace('www.', '', $url) . "/rss"
        ];
        
        foreach ($possibleRssUrls as $possibleRssUrl) {
            if ($this->isValidRssFeed($possibleRssUrl)) {
                return $possibleRssUrl;
            }
        }
        
        return false;
    }
    
    /**
     * Метод для преобразования относительного URL в абсолютный
     * 
     * @param string $baseUrl Базовый URL
     * @param string $relativeUrl Относительный URL
     * @return string Абсолютный URL
     */
    private function makeAbsoluteUrl($baseUrl, $relativeUrl) {
        // Если URL уже абсолютный, возвращаем его
        if (preg_match('~^(?:f|ht)tps?://~i', $relativeUrl)) {
            return $relativeUrl;
        }
        
        $parsedBase = parse_url($baseUrl);
        
        // Если URL начинается с //, добавляем протокол
        if (strpos($relativeUrl, '//') === 0) {
            return isset($parsedBase['scheme']) ? $parsedBase['scheme'] . ':' . $relativeUrl : 'https:' . $relativeUrl;
        }
        
        // Если URL начинается с /, добавляем домен
        if (strpos($relativeUrl, '/') === 0) {
            $scheme = isset($parsedBase['scheme']) ? $parsedBase['scheme'] : 'https';
            $host = isset($parsedBase['host']) ? $parsedBase['host'] : '';
            $port = isset($parsedBase['port']) ? ':' . $parsedBase['port'] : '';
            
            return $scheme . '://' . $host . $port . $relativeUrl;
        }
        
        // В остальных случаях добавляем базовый URL
        $scheme = isset($parsedBase['scheme']) ? $parsedBase['scheme'] : 'https';
        $host = isset($parsedBase['host']) ? $parsedBase['host'] : '';
        $port = isset($parsedBase['port']) ? ':' . $parsedBase['port'] : '';
        $path = isset($parsedBase['path']) ? $parsedBase['path'] : '';
        
        // Если путь не заканчивается на /, добавляем директорию
        if (!empty($path) && substr($path, -1) !== '/') {
            $path = dirname($path) . '/';
        }
        
        return $scheme . '://' . $host . $port . $path . $relativeUrl;
    }
}