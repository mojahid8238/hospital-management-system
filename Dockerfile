FROM php:8.2-apache

# Install iputils-ping
RUN apt-get update && apt-get install -y iputils-ping

# Create a new user and group with UID/GID 1000
RUN groupadd -g 1000 mojahid && \
    useradd -u 1000 -g 1000 -d /home/mojahid mojahid

# Set the Apache user and group
ENV APACHE_RUN_USER mojahid
ENV APACHE_RUN_GROUP mojahid

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN a2enmod rewrite

