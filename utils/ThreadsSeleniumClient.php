<?php
/**
 * Класс для работы с Threads через Selenium
 */
class ThreadsSeleniumClient {
    private $webDriver;
    private $username;
    private $password;
    private $chromeOptions;
    private $isLoggedIn = false;
    
    /**
     * Конструктор класса
     * 
     * @param string $username Имя пользователя
     * @param string $password Пароль
     */
    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
        
        // Инициализация ChromeOptions
        $this->initChromeOptions();
        
        // Инициализация WebDriver
        $this->initWebDriver();
    }
    
    /**
     * Инициализация ChromeOptions
     */
    private function initChromeOptions() {
        $this->chromeOptions = [
            'args' => [
                '--headless',
                '--disable-gpu',
                '--window-size=1920,1080',
                '--no-sandbox',
                '--disable-dev-shm-usage'
            ]
        ];
    }
    
    /**
     * Инициализация WebDriver
     */
    private function initWebDriver() {
        try {
            $capabilities = [
                'browserName' => 'chrome',
                'goog:chromeOptions' => $this->chromeOptions
            ];
            
            $this->webDriver = \Facebook\WebDriver\Remote\RemoteWebDriver::create(
                'http://localhost:4444/wd/hub',
                $capabilities
            );
        } catch (\Exception $e) {
            throw new \Exception('Failed to initialize WebDriver: ' . $e->getMessage());
        }
    }
    
    /**
     * Метод для входа в аккаунт Threads
     * 
     * @return bool Успешность входа
     */
    public function login() {
        try {
            // Открытие страницы входа
            $this->webDriver->get('https://www.threads.net/login');
            
            // Ожидание загрузки страницы
            $this->waitForElement(\Facebook\WebDriver\WebDriverBy::name('username'), 10);
            
            // Ввод имени пользователя
            $this->webDriver->findElement(\Facebook\WebDriver\WebDriverBy::name('username'))->sendKeys($this->username);
            
            // Ввод пароля
            $this->webDriver->findElement(\Facebook\WebDriver\WebDriverBy::name('password'))->sendKeys($this->password);
            
            // Нажатие кнопки входа
            $this->webDriver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//button[@type="submit"]'))->click();
            
            // Ожидание загрузки главной страницы
            $this->waitForElement(\Facebook\WebDriver\WebDriverBy::xpath('//div[@role="main"]'), 15);
            
            $this->isLoggedIn = true;
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Login failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Метод для публикации контента
     * 
     * @param string $content Содержимое публикации
     * @param array $images Массив путей к изображениям для публикации
     * @return bool Успешность публикации
     */
    public function postContent($content, $images = []) {
        try {
            // Проверка авторизации
            if (!$this->isLoggedIn) {
                $this->login();
            }
            
            // Переход на главную страницу
            $this->webDriver->get('https://www.threads.net');
            
            // Ожидание загрузки страницы
            $this->waitForElement(\Facebook\WebDriver\WebDriverBy::xpath('//div[@role="main"]'), 10);
            
            // Нажатие на кнопку создания нового поста
            $this->webDriver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//a[contains(@href, "/create")]'))->click();
            
            // Ожидание загрузки страницы создания поста
            $this->waitForElement(\Facebook\WebDriver\WebDriverBy::xpath('//textarea[@placeholder]'), 10);
            
            // Ввод текста поста
            $this->webDriver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//textarea[@placeholder]'))->sendKeys($content);
            
            // Загрузка изображений, если они есть
            if (!empty($images)) {
                foreach ($images as $image) {
                    // Нажатие на кнопку загрузки изображения
                    $this->webDriver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//input[@type="file"]'))->sendKeys($image);
                    
                    // Ожидание загрузки изображения
                    sleep(2);
                }
            }
            
            // Нажатие на кнопку публикации
            $this->webDriver->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//button[contains(text(), "Post")]'))->click();
            
            // Ожидание завершения публикации
            sleep(5);
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Post creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Метод для ожидания появления элемента
     * 
     * @param \Facebook\WebDriver\WebDriverBy $by Локатор элемента
     * @param int $timeout Таймаут в секундах
     * @return \Facebook\WebDriver\Remote\RemoteWebElement Найденный элемент
     */
    private function waitForElement(\Facebook\WebDriver\WebDriverBy $by, $timeout = 10) {
        $wait = new \Facebook\WebDriver\WebDriverWait($this->webDriver, $timeout);
        return $wait->until(
            \Facebook\WebDriver\WebDriverExpectedCondition::presenceOfElementLocated($by)
        );
    }
    
    /**
     * Метод для закрытия браузера
     */
    public function close() {
        if ($this->webDriver) {
            $this->webDriver->quit();
        }
    }
    
    /**
     * Деструктор класса
     */
    public function __destruct() {
        $this->close();
    }
}
