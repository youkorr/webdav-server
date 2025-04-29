ARG BUILD_FROM=ghcr.io/hassio-addons/base:13.0.0

FROM ${BUILD_FROM}

# Configuration shell
SHELL ["/bin/sh", "-o", "pipefail", "-c"]

# Utiliser S6-Overlay déjà installé dans l'image de base
ENV S6_BEHAVIOUR_IF_STAGE2_FAILS=2

# Installation des paquets nécessaires
RUN \
    apk update \
    && apk add --no-cache \
        apache2 \
        apache2-utils \
        apache2-proxy \
        apache2-ssl \
        php8 \
        php8-apache2 \
        php8-session \
        php8-json \
        php8-fileinfo \
        php8-curl \
        php8-dom \
        php8-xml \
        php8-mbstring \
        php8-zip \
        php8-gd \
        curl \
        jq \
        ca-certificates

# S'assurer que les modules Apache nécessaires sont installés
RUN \
    if [ -d /etc/apache2/conf.d ]; then \
        echo "LoadModule dav_module modules/mod_dav.so" > /etc/apache2/conf.d/dav.conf; \
        echo "LoadModule dav_fs_module modules/mod_dav_fs.so" > /etc/apache2/conf.d/dav_fs.conf; \
    fi

# Téléchargement des bibliothèques JS/CSS
RUN mkdir -p /tmp/web \
    && curl -L -o /tmp/web/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css \
    && curl -L -o /tmp/web/bootstrap.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js \
    && curl -L -o /tmp/web/jquery.min.js https://code.jquery.com/jquery-3.6.0.min.js \
    && curl -L -o /tmp/web/fontawesome.min.css https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css

# Créer les dossiers de l'interface web
RUN mkdir -p /var/www/localhost/htdocs/css \
             /var/www/localhost/htdocs/js \
             /var/www/localhost/htdocs/includes

# Copier les bibliothèques téléchargées
RUN cp /tmp/web/bootstrap.min.css /var/www/localhost/htdocs/css/ \
    && cp /tmp/web/bootstrap.min.js /var/www/localhost/htdocs/js/ \
    && cp /tmp/web/jquery.min.js /var/www/localhost/htdocs/js/ \
    && cp /tmp/web/fontawesome.min.css /var/www/localhost/htdocs/css/ \
    && rm -rf /tmp/web

# Copier les fichiers de l'application
COPY rootfs/ /

# Permissions
RUN chmod +x /etc/s6-overlay/s6-rc.d/apache/run \
    && chmod +x /etc/s6-overlay/s6-rc.d/init/run \
    && chmod +x /usr/local/bin/init.sh

# Exposer le port
EXPOSE 80

# Étiquettes
LABEL \
    io.hass.name="WebDAV Explorer" \
    io.hass.description="WebDAV Server with Web Explorer for Home Assistant" \
    io.hass.arch="aarch64|amd64|armhf|armv7|i386"



