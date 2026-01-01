FROM php:8.2-apache

COPY ./wwwroot /var/www/html
COPY ./download /var/www/html/download
COPY ./feed /var/www/html/feed
COPY ./game /var/www/html/game

COPY php.ini /usr/local/etc/php/conf.d/custom.ini
RUN a2enmod rewrite
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    zlib1g-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install gd
RUN docker-php-ext-install mbstring mysqli pdo pdo_mysql

RUN chown -R www-data:www-data /var/www/html
RUN chown -R www-data:www-data /var/www/html/game

COPY ./BattleEngine /var/www/BattleEngine
RUN gcc /var/www/BattleEngine/battle.c -lm -o /usr/lib/cgi-bin/battle
RUN chmod 755 /usr/lib/cgi-bin/battle
RUN rm -rf /var/www/BattleEngine