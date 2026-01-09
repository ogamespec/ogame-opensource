# Dockerfile for OGame Open Source
# Some points were borrowed from the deployment by Noli: https://gitlab.com/nolialsea/ogame-opensource-docker

# This deployment only implements a single-domain configuration (the lobby and Universe 1 on the same domain). If you need to separate the universe into a subdomain like uni1.mygame.com, you'll need to come up with your own solution.

FROM php:8.2-apache

# MailHog configuration
# Copy the msmtp configuration file into the container
COPY msmtprc /etc/msmtprc
# Set permissions so it's readable by all users
RUN chmod 644 /etc/msmtprc

# Lobby files (start page)
COPY ./wwwroot /var/www/html
COPY ./download /var/www/html/download
# Universe (game) files
COPY ./feed /var/www/html/feed
COPY ./game /var/www/html/game

# PHP extensions
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

# To prevent configuration files from being destroyed after redeployment, you need to make them symbolic links, and drag the configs themselves into the volume
# Create a directory that will be managed by a Docker volume
RUN mkdir -p /var/www/html/persistent_configs
# Change ownership of this directory so the web server can write to it
RUN chown -R www-data:www-data /var/www/html/persistent_configs
# Create two SEPARATE symbolic links for the two config files
RUN ln -s /var/www/html/persistent_configs/root_config.php /var/www/html/config.php
RUN ln -s /var/www/html/persistent_configs/game_config.php /var/www/html/game/config.php

RUN chown -R www-data:www-data /var/www/html

# C battle engine
COPY ./BattleEngine /var/www/BattleEngine
RUN gcc /var/www/BattleEngine/battle.c -lm -o /usr/lib/cgi-bin/battle
RUN chmod 755 /usr/lib/cgi-bin/battle
RUN rm -rf /var/www/BattleEngine