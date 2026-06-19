FROM richarvey/nginx-php-fpm:latest

# Copier les fichiers du projet Laravel
COPY . /var/www/html

# AJOUT : Copier la configuration Nginx personnalisée pour Laravel
COPY conf/nginx.conf /etc/nginx/sites-available/default.conf

# Définir le dossier public comme racine Web
ENV WEBROOT /var/www/html/public
ENV APP_ENV production
ENV APP_DEBUG false

# Installer les dépendances via Composer
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Configuration des permissions d'écriture
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Créer un script de démarrage automatique pour les migrations
RUN mkdir -p /var/www/html/after_deploy
RUN echo "php artisan migrate --force" > /var/www/html/after_deploy/01_migrate.sh
RUN chmod +x /var/www/html/after_deploy/01_migrate.sh