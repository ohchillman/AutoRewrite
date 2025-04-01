FROM webdevops/php-nginx:8.3-alpine

ENV PHP_DATE_TIMEZONE="Europe/Moscow" \
    TZ=Europe/Moscow \
    php.session.name=session \
    fpm.pool.pm=ondemand \
    fpm.pool.pm.max_children=100 \
    fpm.pool.pm.max_requests=1000

# Скрываем версию NGINX
RUN echo 'server_tokens off;' >> /opt/docker/etc/nginx/main.conf

# Копируем доп. настройки хоста NGINX в образ
COPY nginx.conf /opt/docker/etc/nginx/vhost.common.d/nginx.conf

COPY src /app
RUN composer install -d /app || true

# crontab
RUN (crontab -l 2>/dev/null; echo '0 * * * * curl -s localhost/cron/parse.php') | crontab -
RUN (crontab -l 2>/dev/null; echo '0 */2 * * * curl -s localhost/cron/rewrite.php') | crontab -
RUN (crontab -l 2>/dev/null; echo '0 */3 * * * curl -s localhost/cron/post.php') | crontab -

EXPOSE 80

CMD ["supervisord"]