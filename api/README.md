# Twitter API для публикации твитов

Простой API для публикации твитов в Twitter через HTTP-запросы.

## Установка и запуск

1. Клонируйте репозиторий:
```bash
git clone https://github.com/ohchillman/AutoRewrite.git
cd AutoRewrite
```

2. Запустите сервер:
```bash
cd api
php server.php
```

Сервер будет запущен на `http://localhost:5000/`.

## Использование API

### Публикация твита

**Endpoint:** `http://localhost:5000/`

**Метод:** `POST`

**Формат запроса:**
```json
{
  "credentials": {
    "access_secret": "ваш_access_secret",
    "access_token": "ваш_access_token",
    "api_key": "ваш_api_key",
    "api_secret": "ваш_api_secret"
  },
  "has_image": false,
  "proxy_settings": null,
  "text": "Текст твита"
}
```

**Параметры:**
- `credentials` (обязательный) - учетные данные для доступа к Twitter API:
  - `access_secret` - секрет токена доступа
  - `access_token` - токен доступа
  - `api_key` - ключ API (Consumer Key)
  - `api_secret` - секрет API (Consumer Secret)
- `text` (обязательный) - текст твита
- `has_image` (опциональный) - флаг наличия изображения (в текущей версии поддерживается только `false`)
- `proxy_settings` (опциональный) - настройки прокси в формате:
  ```json
  {
    "host": "proxy.example.com",
    "port": 8080,
    "username": "user",
    "password": "pass",
    "type": "http" // или "socks4", "socks5"
  }
  ```

**Пример успешного ответа:**
```json
{
  "status": "success",
  "tweet_id": "1910778516813066601",
  "tweet_type": "text_only",
  "tweet_url": "https://twitter.com/user/status/1910778516813066601"
}
```

**Пример ответа с ошибкой:**
```json
{
  "status": "error",
  "message": "Error posting tweet: Invalid or expired token"
}
```

## Пример использования с curl

```bash
curl -X POST -H "Content-Type: application/json" -d '{
  "credentials": {
    "access_secret": "ваш_access_secret",
    "access_token": "ваш_access_token",
    "api_key": "ваш_api_key",
    "api_secret": "ваш_api_secret"
  },
  "has_image": false,
  "proxy_settings": null,
  "text": "Тестовый твит для проверки API"
}' http://localhost:5000/
```

## Логирование

Все действия API логируются в файл `twitter_api.log` в директории API.
