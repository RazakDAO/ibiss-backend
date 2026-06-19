FROM richarvey/nginx-php-fpm:latest

# Copier les fichiers du projet Laravel
COPY . /var/www/html

# Définir le dossier public comme racine Web
ENV WEBROOT /var/www/html/public
ENV APP_ENV production
ENV APP_DEBUG false

# CORRECTION : Ajout du flag --ignore-platform-reqs pour ignorer les extensions manquantes au build
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Appliquer les permissions de stockage requises par Laravel
RUN chown -R nw:nw /var/www/html/storage /var/www/html/bootstrap/cache

# Lancer les migrations de base de données automatiquement au démarrage
ENTRYPOINT ["sh", "-c", "php artisan migrate --force && /start.sh"]