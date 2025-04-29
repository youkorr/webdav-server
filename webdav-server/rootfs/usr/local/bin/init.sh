#!/usr/bin/with-contenv bash

# Lire la configuration
CONFIG_PATH=/data/options.json

USERNAME=$(jq --raw-output ".username" $CONFIG_PATH)
PASSWORD=$(jq --raw-output ".password" $CONFIG_PATH)
BASE_PATH=$(jq --raw-output ".base_path" $CONFIG_PATH)
READ_ONLY=$(jq --raw-output ".read_only" $CONFIG_PATH)
AUTH_REQUIRED=$(jq --raw-output ".auth_required" $CONFIG_PATH)

# S'assurer que le dossier base existe
mkdir -p "${BASE_PATH}"

# Définir les permissions
if [[ "$READ_ONLY" == "true" ]]; then
  chmod -R 755 "${BASE_PATH}"
else
  chmod -R 777 "${BASE_PATH}"
  chown -R www-data:www-data "${BASE_PATH}"
fi

# Configurer l'authentification
if [[ "$AUTH_REQUIRED" == "true" ]]; then
  # Créer un fichier d'authentification pour Apache
  htpasswd -bc /etc/apache2/.htpasswd "${USERNAME}" "${PASSWORD}"
  sed -i "s|Require all granted|AuthType Basic\n        AuthName \"WebDAV Server\"\n        AuthUserFile /etc/apache2/.htpasswd\n        Require valid-user|g" /etc/apache2/sites-available/webdav.conf
fi

# Activer les modules nécessaires
a2enmod dav
a2enmod dav_fs
a2enmod auth_digest
a2enmod authn_file

# Activer le site WebDAV
a2ensite webdav

# Corriger les permissions des logs
mkdir -p /var/log/apache2
chmod -R 777 /var/log/apache2

# Signal que l'initialisation est terminée
touch /var/run/init_done
