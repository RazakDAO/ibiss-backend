FROM richarvey/nginx-php-fpm:latest

# Copier tous les fichiers du projet Laravel dans le serveur
COPY . /var/www/html

# Indiquer à Nginx où se trouve le dossier public de Laravel
ENV WEBROOT /var/www/html/public
ENV APP_ENV production
ENV APP_DEBUG false

# Installer les dépendances du projet sans les outils de développement
RUN composer install --no-dev --optimize-autoloader

# Donner les permissions nécessaires à Laravel pour écrire les logs et le cache
RUN chown -R nw:nw /var/www/html/storage /var/www/html/bootstrap/cache