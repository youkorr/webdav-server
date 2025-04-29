ARG BUILD_FROM=ghcr.io/hassio-addons/base:13.0.0

FROM ${BUILD_FROM}

# Configuration shell
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# Utiliser S6-Overlay déjà installé dans l'image de base
ENV S6_BEHAVIOUR_IF_STAGE2_FAILS=2

# Installation des paquets nécessaires
RUN \
    apt-get update \
    && apt-get install -y --no-install-recommends \
        apache2 \
        apache2-utils \
        libapache2-mod-php \
        php \
        php-curl \
        php-xml \
        php-zip \
        php-gd \
        php-mbstring \
        php-json \
        php-fileinfo \
        ca-certificates \
        jq \
    && a2enmod dav \
    && a2enmod dav_fs \
    && a2enmod auth_digest \
    && a2enmod rewrite \
    && a2enmod ssl \
    && a2enmod php \
    && a2enmod headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Téléchargement des bibliothèques JS/CSS
RUN mkdir -p /tmp/webui \
    && curl -L -o /tmp/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css \
    && curl -L -o /tmp/bootstrap.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js \
    && curl -L -o /tmp/jquery.min.js https://code.jquery.com/jquery-3.6.0.min.js \
    && curl -L -o /tmp/fontawesome.min.css https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css

# Créer les dossiers de l'interface web
RUN mkdir -p /var/www/html/css \
             /var/www/html/js \
             /var/www/html/includes

# Copier les bibliothèques téléchargées
RUN cp /tmp/bootstrap.min.css /var/www/html/css/ \
    && cp /tmp/bootstrap.min.js /var/www/html/js/ \
    && cp /tmp/jquery.min.js /var/www/html/js/ \
    && cp /tmp/fontawesome.min.css /var/www/html/css/ \
    && rm -rf /tmp/*

# Copier les fichiers de l'application
COPY rootfs/ /

# Site WebDAV par défaut
COPY rootfs/etc/apache2/sites-available/webdav.conf /etc/apache2/sites-available/webdav.conf
RUN a2ensite webdav

# Permissions
RUN chmod +x /etc/s6-overlay/s6-rc.d/apache/run \
    && chmod +x /etc/s6-overlay/s6-rc.d/init/run \
    && chmod +x /usr/local/bin/init.sh \
    && chmod 755 /etc/apache2

# Exposer le port
EXPOSE 80

# Étiquettes
LABEL \
    io.hass.name="WebDAV Explorer" \
    io.hass.description="WebDAV Server with Web Explorer for Home Assistant" \
    io.hass.version="${BUILD_VERSION}" \
    io.hass.type="addon" \
    io.hass.arch="aarch64|amd64|armhf|armv7|i386"


