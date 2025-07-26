FROM php:8.2-fpm

# ставим необходимые для нормальной работы модули
RUN apt-get update && apt-get install -y \
        sudo \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libonig-dev \
        libzip-dev \
        libmemcached-dev zlib1g-dev libssl-dev \
        libmcrypt-dev \
        libpq-dev \
        && docker-php-ext-install pdo_pgsql \
        && pecl install mcrypt-1.0.6 \
        && docker-php-ext-enable mcrypt \
        && docker-php-ext-install -j$(nproc) iconv mbstring mysqli pdo_mysql zip \
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        && docker-php-ext-install -j$(nproc) gd \
        && pecl install redis \
        && docker-php-ext-enable redis \
        && pecl install mongodb \
        && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
        && echo "extension=mongodb.so" >> /usr/local/etc/php/php.ini \
        && pecl install memcached && \
        docker-php-ext-enable memcached

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /data

ARG UID=1000

RUN groupadd dev -g ${UID} 
RUN useradd -u ${UID} -g dev -d /home/dev -m
RUN echo '%dev ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers
USER dev:dev

CMD ["php-fpm"]
