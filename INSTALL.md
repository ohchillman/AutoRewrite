# Инструкция по установке AutoRewrite на Ubuntu

Данная инструкция описывает процесс развертывания сервиса AutoRewrite на чистом сервере Ubuntu.

## Требования

- Ubuntu 20.04 или новее
- Права суперпользователя (sudo)
- Доступ к интернету для установки пакетов

## Шаг 1: Обновление системы

```bash
sudo apt-get update
sudo apt-get upgrade -y
```

## Шаг 2: Установка необходимых пакетов

```bash
# Установка Apache, PHP и MySQL
sudo apt-get install -y apache2 mysql-server php libapache2-mod-php php-mysql php-curl php-json php-mbstring php-xml php-zip unzip git

# Включение модуля mod_rewrite для Apache
sudo a2enmod rewrite

# Перезапуск Apache
sudo systemctl restart apache2
```

## Шаг 3: Настройка MySQL

```bash
# Запуск MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Создание базы данных и пользователя
sudo mysql -e "CREATE DATABASE autorewrite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'autorewrite'@'localhost' IDENTIFIED BY 'autorewrite_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON autorewrite.* TO 'autorewrite'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

> **Примечание**: В реальной среде рекомендуется использовать более сложный пароль. Не забудьте изменить пароль в файле конфигурации базы данных.

## Шаг 4: Клонирование репозитория

```bash
# Переход в директорию веб-сервера
cd /var/www/html

# Клонирование репозитория
sudo git clone https://github.com/ohchillman/AutoRewrite.git

# Установка прав доступа
sudo chown -R www-data:www-data /var/www/html/AutoRewrite
sudo chmod -R 755 /var/www/html/AutoRewrite
```

## Шаг 5: Настройка базы данных

```bash
# Импорт схемы базы данных
sudo mysql autorewrite < /var/www/html/AutoRewrite/database/schema.sql
```

## Шаг 6: Настройка конфигурации

Отредактируйте файл конфигурации базы данных, если вы изменили пароль на шаге 3:

```bash
sudo nano /var/www/html/AutoRewrite/config/database.php
```

Убедитесь, что настройки соответствуют вашей конфигурации:

```php
return [
    'host' => 'localhost',
    'database' => 'autorewrite',
    'username' => 'autorewrite',
    'password' => 'autorewrite_password', // Измените на ваш пароль
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
```

## Шаг 7: Настройка виртуального хоста Apache

Создайте файл конфигурации виртуального хоста:

```bash
sudo nano /etc/apache2/sites-available/autorewrite.conf
```

Добавьте следующую конфигурацию:

```apache
<VirtualHost *:80>
    ServerName autorewrite.local
    DocumentRoot /var/www/html/AutoRewrite
    
    <Directory /var/www/html/AutoRewrite>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/autorewrite_error.log
    CustomLog ${APACHE_LOG_DIR}/autorewrite_access.log combined
</VirtualHost>
```

> **Примечание**: Замените `autorewrite.local` на ваше доменное имя, если оно у вас есть. В противном случае, вы можете добавить запись в файл `/etc/hosts` для локального тестирования.

Активируйте виртуальный хост и перезапустите Apache:

```bash
sudo a2ensite autorewrite.conf
sudo systemctl reload apache2
```

## Шаг 8: Настройка .htaccess

Убедитесь, что файл .htaccess в корневой директории проекта имеет правильные права доступа:

```bash
sudo chmod 644 /var/www/html/AutoRewrite/.htaccess
```

## Шаг 9: Проверка установки

Откройте браузер и перейдите по адресу:
- `http://ваш_сервер/AutoRewrite` (если вы не настраивали виртуальный хост)
- `http://autorewrite.local` (если вы настроили виртуальный хост и добавили запись в /etc/hosts)

Вы должны увидеть главную страницу сервиса AutoRewrite.

## Шаг 10: Настройка сервиса

После успешной установки вам необходимо настроить сервис через веб-интерфейс:

1. Перейдите на страницу "Настройки" и добавьте API ключи для Make.com и других сервисов.
2. Настройте прокси на странице "Прокси".
3. Добавьте аккаунты социальных сетей на странице "Аккаунты".
4. Настройте источники для парсинга на странице "Настройки парсинга".

## Возможные проблемы и их решения

### Проблема: Ошибка подключения к базе данных

**Решение**:
1. Проверьте, запущен ли MySQL: `sudo systemctl status mysql`
2. Проверьте настройки подключения в файле `config/database.php`
3. Убедитесь, что пользователь базы данных имеет правильные права доступа

### Проблема: Ошибка 404 при переходе по URL

**Решение**:
1. Убедитесь, что модуль mod_rewrite включен: `sudo a2enmod rewrite`
2. Проверьте, что в конфигурации Apache для директории проекта установлен параметр `AllowOverride All`
3. Перезапустите Apache: `sudo systemctl restart apache2`

### Проблема: Ошибка прав доступа к файлам

**Решение**:
```bash
sudo chown -R www-data:www-data /var/www/html/AutoRewrite
sudo chmod -R 755 /var/www/html/AutoRewrite
sudo chmod 644 /var/www/html/AutoRewrite/.htaccess
```

### Проблема: Ошибка при парсинге или реврайте контента

**Решение**:
1. Проверьте, что PHP расширения curl, json и mbstring установлены
2. Убедитесь, что API ключи правильно настроены в разделе "Настройки"
3. Проверьте логи ошибок Apache: `sudo tail -f /var/log/apache2/error.log`

## Дополнительные настройки

### Настройка SSL (HTTPS)

Для защищенного соединения рекомендуется настроить SSL:

```bash
sudo apt-get install -y certbot python3-certbot-apache
sudo certbot --apache -d ваш_домен
```

### Настройка cron-задач для автоматического парсинга

Для автоматического парсинга контента добавьте cron-задачу:

```bash
sudo crontab -e
```

Добавьте следующую строку для запуска парсинга каждый час:

```
0 * * * * php /var/www/html/AutoRewrite/cron/parse.php
```

### Настройка Selenium для Threads

Для работы с Threads через Selenium необходимо установить дополнительные компоненты:

```bash
# Установка Chrome и ChromeDriver
sudo apt-get install -y chromium-browser
wget https://chromedriver.storage.googleapis.com/94.0.4606.61/chromedriver_linux64.zip
unzip chromedriver_linux64.zip
sudo mv chromedriver /usr/bin/chromedriver
sudo chmod +x /usr/bin/chromedriver

# Установка PHP расширения для Selenium
sudo apt-get install -y php-dev php-pear
sudo pecl install selenium
```

## Заключение

Теперь ваш сервис AutoRewrite должен быть полностью установлен и готов к использованию. При возникновении проблем обращайтесь к логам Apache и PHP для получения дополнительной информации об ошибках.
