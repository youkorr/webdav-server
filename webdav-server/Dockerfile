ARG BUILD_FROM=ghcr.io/home-assistant/amd64-base:3.15

FROM ${BUILD_FROM}

# Installer les dépendances
RUN apk add --no-cache \
    apache2 \
    apache2-utils \
    apache2-proxy \
    apache2-ssl \
    apache2-webdav \
    php8-apache2 \
    php8 \
    php8-session \
    php8-json \
    php8-fileinfo \
    php8-curl \
    php8-dom \
    php8-xml \
    php8-mbstring \
    php8-zip \
    php8-gd \
    s6 \
    bash \
    jq \
    curl \
    ca-certificates

# Téléchargement des bibliothèques JS/CSS
RUN mkdir -p /tmp/webui && \
    curl -L -o /tmp/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css && \
    curl -L -o /tmp/bootstrap.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js && \
    curl -L -o /tmp/jquery.min.js https://code.jquery.com/jquery-3.6.0.min.js && \
    curl -L -o /tmp/fontawesome.min.css https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css

# Créer les dossiers de l'interface web
RUN mkdir -p /usr/local/webui/css \
             /usr/local/webui/js \
             /usr/local/webui/includes

# Copier les bibliothèques téléchargées
RUN cp /tmp/bootstrap.min.css /usr/local/webui/css/ && \
    cp /tmp/bootstrap.min.js /usr/local/webui/js/ && \
    cp /tmp/jquery.min.js /usr/local/webui/js/ && \
    cp /tmp/fontawesome.min.css /usr/local/webui/css/

# Copier les fichiers de configuration
COPY rootfs /

# Script d'exécution
COPY run.sh /
RUN chmod a+x /run.sh

# Exposer le port
EXPOSE 80

# S6-Overlay & Init
ENTRYPOINT ["/init"]
CMD ["/run.sh"]
