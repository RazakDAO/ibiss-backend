FROM richarvey/nginx-php-fpm:latest

# Copier les fichiers du projet Laravel
COPY . /var/www/html

# Définir le dossier public comme racine Web
ENV WEBROOT /var/www/html/public
ENV APP_ENV production
ENV APP_DEBUG false

# Installer les dépendances via Composer
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# CORRECTION : Attribution des permissions d'écriture universelles (R/W) sur le stockage
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Lancer les migrations de base de données automatiquement au démarrage
ENTRYPOINT ["sh", "-c", "php artisan migrate --force && /start.sh"]