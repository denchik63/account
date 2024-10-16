FROM php:8.3-cli

RUN apt update && apt install -y \
    libpq-dev \
    locales \
    libicu-dev \
    libzip-dev \
    g++ \
    git \
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libghc-postgresql-libpq-dev \
    libc-client2007e-dev \
    libkrb5-dev

RUN pecl install redis
RUN pecl install xdebug-3.3.2

#RUN docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-png-dir=/usr/include/ --with-jpeg-dir=/usr/include/
#RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/include/postgresql/
RUN docker-php-ext-configure calendar
RUN docker-php-ext-install intl pgsql pdo_pgsql bcmath zip calendar pcntl
RUN docker-php-ext-enable redis xdebug

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/account

RUN mkdir -p /var/www/account/var
RUN chown -R www-data:www-data /var/www/account/var

USER www-data

COPY .inputrc /home/www-data/.inputrc
COPY .bashrc /home/www-data/.bashrc
