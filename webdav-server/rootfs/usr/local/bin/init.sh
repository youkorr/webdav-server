#!/command/with-contenv sh

# Lire la configuration
CONFIG_PATH=/data/options.json

# Extraire les options de configuration
USERNAME=$(jq -r '.username' $CONFIG_PATH)
PASSWORD=$(jq -r '.password' $CONFIG_PATH)
BASE_PATH=$(jq -r '.base_path' $CONFIG_PATH)
READ_ONLY=$(jq -r '.read_only' $CONFIG_PATH)
AUTH_REQUIRED=$(jq -r '.auth_required' $CONFIG_PATH)

# S'assurer que le dossier base existe
echo "Configuring base path: ${BASE_PATH}"
mkdir -p "${BASE_PATH}"

# Définir les permissions
if [ "$READ_ONLY" = "true" ]; then
  chmod -R 755 "${BASE_PATH}"
  echo "Setting read-only permissions"
else
  chmod -R 777 "${BASE_PATH}"
  echo "Setting read-write permissions"
fi

# Configurer l'authentification
if [ "$AUTH_REQUIRED" = "true" ]; then
  # Créer un fichier d'authentification pour Apache
  htpasswd -bc /etc/apache2/.htpasswd "${USERNAME}" "${PASSWORD}"
  sed -i "s|Require all granted|AuthType Basic\n        AuthName \"WebDAV Server\"\n        AuthUserFile /etc/apache2/.htpasswd\n        Require valid-user|g" /etc/apache2/conf.d/webdav.conf
  echo "Authentication enabled for user: ${USERNAME}"
else
  echo "Authentication disabled"
fi

# Remplacer le chemin de base dans la configuration
sed -i "s|%BASE_PATH%|${BASE_PATH}|g" /etc/apache2/conf.d/webdav.conf

# Corriger les permissions pour les logs
mkdir -p /var/log/apache2
chmod -R 777 /var/log/apache2

echo "WebDAV server initialized"


