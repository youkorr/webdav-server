#!/usr/bin/with-contenv bashio

# Lire la configuration
CONFIG_PATH=/data/options.json

USERNAME=$(bashio::config 'username')
PASSWORD=$(bashio::config 'password')
BASE_PATH=$(bashio::config 'base_path')
READ_ONLY=$(bashio::config 'read_only')
AUTH_REQUIRED=$(bashio::config 'auth_required')

# S'assurer que le dossier base existe
mkdir -p "${BASE_PATH}"

# Définir les permissions
if [[ "$READ_ONLY" == "true" ]]; then
  chmod -R 755 "${BASE_PATH}"
else
  chmod -R 777 "${BASE_PATH}"
fi

# Configurer l'authentification
if [[ "$AUTH_REQUIRED" == "true" ]]; then
  # Créer un fichier d'authentification pour Apache
  htpasswd -bc /etc/apache2/.htpasswd "${USERNAME}" "${PASSWORD}"
  WEBDAV_AUTH="AuthType Basic\n  AuthName \"WebDAV Server\"\n  AuthUserFile /etc/apache2/.htpasswd\n  Require valid-user"
else
  WEBDAV_AUTH="Require all granted"
fi

# Remplacer les variables dans la configuration Apache
sed -i "s|%BASE_PATH%|${BASE_PATH}|g" /etc/apache2/sites-available/webdav.conf
sed -i "s|%WEBDAV_AUTH%|${WEBDAV_AUTH}|g" /etc/apache2/sites-available/webdav.conf

# Activer le site WebDAV
ln -sf /etc/apache2/sites-available/webdav.conf /etc/apache2/sites-enabled/webdav.conf

# Démarrer Apache en premier plan
exec apache2 -D FOREGROUND
