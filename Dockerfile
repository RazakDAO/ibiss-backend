FROM richarvey/nginx-php-fpm:latest

# Copier les fichiers du projet Laravel
COPY . /var/www/html

# Activer la réécriture d'URL Laravel (Permet d'éviter les 404 sur les routes)
ENV LOG_STREAM=/dev/stdout
ENV WITH_XDEBUG=false
ENV WEBROOT /var/www/html/public
ENV APP_ENV production
ENV APP_DEBUG false

EXPOSE 80

# Installer les dépendances via Composer
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Configuration des permissions d'écriture
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Fait attendre Nginx que le socket PHP-FPM existe avant de démarrer,
# pour éviter un 502 sur les toutes premières requêtes (voir docker/start-nginx-wait.sh)
RUN cp /var/www/html/docker/start-nginx-wait.sh /usr/local/bin/start-nginx-wait.sh \
    && chmod +x /usr/local/bin/start-nginx-wait.sh \
    && sed -i 's#^command=/usr/sbin/nginx -g "daemon off; error_log /dev/stderr info;"$#command=/usr/local/bin/start-nginx-wait.sh#' /etc/supervisord.conf \
    && grep -q 'start-nginx-wait.sh' /etc/supervisord.conf