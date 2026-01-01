FROM php:8.2-apache
ENV MYSQL_ROOT_PASSWORD=change_me_in_production
COPY ./wwwroot /var/www/html/
COPY ./download /var/www/html/download/
COPY ./feed /var/www/html/feed/
COPY ./game /var/www/html/game/
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
EXPOSE 80