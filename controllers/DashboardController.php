<?php
/**
 * Контроллер для главной страницы (дашборда)
 */
class DashboardController extends BaseController {
    
    /**
     * Отображение главной страницы
     */
    public function index() {
        // Получаем статистику из базы данных
        $stats = $this->getStats();
        
        // Отображаем представление
        $this->render('dashboard/index', [
            'title' => 'Главная - AutoRewrite',
            'pageTitle' => 'Панель управления',
            'currentPage' => 'dashboard',
            'layout' => 'main',
            'stats' => $stats
        ]);
    }
    
    /**
     * Получение статистики для дашборда
     * 
     * @return array Массив со статистикой
     */
    private function getStats() {
        $db = $this->db->getConnection();
        
        // Количество активных аккаунтов
        $accountsCount = $this->db->fetchColumn("SELECT COUNT(*) FROM accounts WHERE is_active = 1");
        
        // Количество активных прокси
        $proxiesCount = $this->db->fetchColumn("SELECT COUNT(*) FROM proxies WHERE is_active = 1");
        
        // Количество источников парсинга
        $sourcesCount = $this->db->fetchColumn("SELECT COUNT(*) FROM parsing_sources WHERE is_active = 1");
        
        // Количество оригинального контента
        $originalContentCount = $this->db->fetchColumn("SELECT COUNT(*) FROM original_content");
        
        // Количество реврайтнутого контента
        $rewrittenContentCount = $this->db->fetchColumn("SELECT COUNT(*) FROM rewritten_content");
        
        // Количество опубликованных постов
        $postedCount = $this->db->fetchColumn("SELECT COUNT(*) FROM posts WHERE status = 'posted'");
        
        // Последние реврайтнутые посты
        $latestRewrittenContent = $this->db->fetchAll("
            SELECT r.id, r.title, r.content, r.rewrite_date, r.status, o.url
            FROM rewritten_content r
            JOIN original_content o ON r.original_id = o.id
            ORDER BY r.rewrite_date DESC
            LIMIT 5
        ");
        
        return [
            'accountsCount' => $accountsCount,
            'proxiesCount' => $proxiesCount,
            'sourcesCount' => $sourcesCount,
            'originalContentCount' => $originalContentCount,
            'rewrittenContentCount' => $rewrittenContentCount,
            'postedCount' => $postedCount,
            'latestRewrittenContent' => $latestRewrittenContent
        ];
    }
}
