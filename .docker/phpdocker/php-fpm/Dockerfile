FROM php:7.4-fpm
WORKDIR "/app"

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
        install-php-extensions curl dom exif ftp gd json mbstring pdo pdo_pgsql posix pgsql redis simplexml soap xdebug xmlreader zip \
        && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer