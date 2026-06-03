# 公式イメージを指定
FROM php:8.3-fpm-bullseye

ARG UID=1000
ARG GID=1000

RUN apt-get update && \
    apt-get -y install --no-install-recommends git unzip libzip-dev libicu-dev libonig-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install intl pdo_mysql zip bcmath

RUN groupmod -g ${GID} www-data && \
    usermod -u ${UID} www-data

WORKDIR /var/www/html

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN sed -i "s/user = www-data/user = www-data/" /usr/local/etc/php-fpm.d/www.conf && \
    sed -i "s/group = www-data/group = www-data/" /usr/local/etc/php-fpm.d/www.conf

USER www-data

CMD ["php-fpm", "-F"]