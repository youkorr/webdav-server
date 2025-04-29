#!/usr/bin/with-contenv bashio

# Lire la configuration
CONFIG_PATH=/data/options.json

USERNAME=$(bashio::config 'username')
PASSWORD=$(bashio::config 'password')
BASE_PATH=$(bashio::config 'base_path')
READ_ONLY=$(bashio::config 'read_only')
AUTH_REQUIRED=$(bashio::config 'auth_required')

# S'assurer que le dossier base existe
bashio::log.info "Configuring base path: ${BASE_PATH}"
mkdir -p "${BASE_PATH}"

# Définir les permissions
if [[ "$READ_ONLY" == "true" ]]; then
  chmod -R 755 "${BASE_PATH}"
  bashio::log.info "Setting read-only permissions"
else
  chmod -R 777 "${BASE_PATH}"
  chown -R www-data:www-data "${BASE_PATH}"
  bashio::log.info "Setting read-write permissions"
fi

# Configurer l'authentification
if [[ "$AUTH_REQUIRED" == "true" ]]; then
  # Créer un fichier d'authentification pour Apache
  htpasswd -bc /etc/apache2/.htpasswd "${USERNAME}" "${PASSWORD}"
  sed -i "s|Require all granted|AuthType Basic\n        AuthName \"WebDAV Server\"\n        AuthUserFile /etc/apache2/.htpasswd\n        Require valid-user|g" /etc/apache2/sites-available/webdav.conf
  bashio::log.info "Authentication enabled for user: ${USERNAME}"
else
  bashio::log.info "Authentication disabled"
fi

# Remplacer le chemin de base dans la configuration
sed -i "s|%BASE_PATH%|${BASE_PATH}|g" /etc/apache2/sites-available/webdav.conf

# Activer les modules nécessaires
a2enmod dav
a2enmod dav_fs
a2enmod auth_digest
a2enmod authn_file

# S'assurer que le site WebDAV est activé
a2ensite webdav

# Corriger les permissions pour les logs
mkdir -p /var/log/apache2
chmod -R 777 /var/log/apache2

# Copier les fichiers PHP dans le répertoire web
cp -R /var/www/html/* /var/www/webdav/

bashio::log.info "WebDAV server initialized"

