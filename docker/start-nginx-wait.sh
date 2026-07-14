#!/bin/sh
# Attend que PHP-FPM ait créé son socket avant de démarrer Nginx.
# Sans ça, Nginx (priority=10) peut démarrer et recevoir des requêtes
# (notamment le health check de la plateforme d'hébergement) avant que
# PHP-FPM (priority=5) n'ait fini de créer /var/run/php-fpm.sock,
# ce qui provoque un 502 sur les toutes premières requêtes.
until [ -S /var/run/php-fpm.sock ]; do
  sleep 0.2
done

exec /usr/sbin/nginx -g "daemon off; error_log /dev/stderr info;"
