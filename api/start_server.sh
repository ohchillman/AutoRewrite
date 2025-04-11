# Запуск сервера в фоновом режиме для тестирования
php -S 0.0.0.0:5000 -t /home/ubuntu/AutoRewrite/api/ > /dev/null 2>&1 &
echo $! > /tmp/twitter_api_server.pid
echo "Сервер запущен на порту 5000"
