#!/bin/bash

# Скрипт для тестирования API публикации твитов

# Проверяем, запущен ли сервер
if ! nc -z localhost 5000 >/dev/null 2>&1; then
    echo "Сервер не запущен на порту 5000. Запустите сервер командой: php api/server.php"
    exit 1
fi

# Данные для запроса
read -r -d '' JSON_DATA << EOM
{
  "credentials": {
    "access_secret": "***Dz42",
    "access_token": "1909391055771914240-GfCgyGRiknD52FrRCigcyEgimS7q4x",
    "api_key": "PXFlkJajyvX8D1SfSNBhMInGa",
    "api_secret": "***qs2U"
  },
  "has_image": false,
  "proxy_settings": null,
  "text": "Тестовый твит для проверки API $(date)"
}
EOM

# Отправляем запрос
echo "Отправка тестового твита..."
curl -s -X POST -H "Content-Type: application/json" -d "$JSON_DATA" http://localhost:5000/

echo -e "\n\nЗапрос отправлен. Проверьте результат выше."
