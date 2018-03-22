FROM php:7.2.3-apache

apt-get update && apt-get install -y libmemcached-dev zlib1g-dev \
    && pecl install memcached-2.2.0 \
    && docker-php-ext-enable memcached \
	&& docker-php-ext-install -j$(nproc) mysqli