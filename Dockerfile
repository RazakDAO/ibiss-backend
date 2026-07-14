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