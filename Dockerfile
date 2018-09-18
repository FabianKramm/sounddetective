FROM php:7.2.3-apache

RUN apt-get update && apt-get install -y libmemcached-dev zlib1g-dev libpq-dev libmemcached-dev curl \
    && docker-php-ext-install -j$(nproc) mysqli

# Install Memcached for php 7
RUN curl -L -o /tmp/memcached.tar.gz "https://github.com/php-memcached-dev/php-memcached/archive/php7.tar.gz" \
    && mkdir -p /usr/src/php/ext/memcached \
    && tar -C /usr/src/php/ext/memcached -zxvf /tmp/memcached.tar.gz --strip 1 \
    && docker-php-ext-configure memcached \
    && docker-php-ext-install memcached \
    && rm /tmp/memcached.tar.gz 

ADD . /var/www/html/
ADD ./config /usr/local/etc/php/