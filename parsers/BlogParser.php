<?php

class BlogParser {
    private $url;
    private $limit = 20;

    public function __construct($url, $limit = 20) {
        $this->url = $url;
        $this->limit = $limit;
    }

    public function parse() {
        try {
            // Загружаем HTML-страницу
            $html = file_get_contents($this->url);
            if (!$html) {
                throw new Exception("Не удалось загрузить страницу");
            }

            // Создаем DOM-объект
            $dom = new DOMDocument();
            @$dom->loadHTML($html, LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);

            // Пытаемся найти статьи на странице
            $items = [];
            $articles = $xpath->query("//article | //div[contains(@class, 'post')] | //div[contains(@class, 'article')]");

            foreach ($articles as $article) {
                if (count($items) >= $this->limit) {
                    break;
                }

                // Извлекаем заголовок
                $titleNode = $xpath->query(".//h1 | .//h2 | .//h3", $article)->item(0);
                $title = $titleNode ? trim($titleNode->textContent) : '';

                // Извлекаем ссылку
                $linkNode = $xpath->query(".//a[contains(@class, 'read-more')] | .//h2/a | .//h1/a", $article)->item(0);
                $link = $linkNode ? $linkNode->getAttribute('href') : '';

                // Извлекаем контент
                $contentNode = $xpath->query(".//div[contains(@class, 'content')] | .//div[contains(@class, 'entry')]", $article)->item(0);
                $content = $contentNode ? trim($contentNode->textContent) : '';

                // Извлекаем автора
                $authorNode = $xpath->query(".//span[contains(@class, 'author')] | .//a[contains(@class, 'author')]", $article)->item(0);
                $author = $authorNode ? trim($authorNode->textContent) : '';

                // Извлекаем дату
                $dateNode = $xpath->query(".//time | .//span[contains(@class, 'date')]", $article)->item(0);
                $date = $dateNode ? strtotime(trim($dateNode->textContent)) : time();

                if ($title && $link) {
                    $items[] = [
                        'title' => $title,
                        'description' => $content,
                        'link' => $link,
                        'author' => $author,
                        'timestamp' => $date
                    ];
                }
            }

            return $items;
        } catch (Exception $e) {
            throw new Exception("Ошибка при парсинге блога: " . $e->getMessage());
        }
    }
} 