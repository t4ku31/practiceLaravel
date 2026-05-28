# 公式イメージを指定
FROM php:8.3-fpm-bullseye

RUN apt-get update && \
    apt-get -y install --no-install-recommends git unzip libzip-dev libicu-dev libonig-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install intl pdo_mysql zip bcmath

RUN groupadd -g 1002 appgroup && \
    usermod -aG appgroup www-data

WORKDIR /var/www/html

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

CMD ["php-fpm", "-F"]