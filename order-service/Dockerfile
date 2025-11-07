FROM php:8.2-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    rabbitmq-c-dev \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip

# Install AMQP extension
RUN pecl install amqp && docker-php-ext-enable amqp

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy shared bundle first (needed for composer)
COPY shared-bundle /var/www/shared-bundle

# Copy composer files
COPY order-service/composer.json order-service/composer.lock* ./

# Update composer.json to use correct path for shared-bundle in Docker
RUN sed -i 's|"url": "../shared-bundle"|"url": "/var/www/shared-bundle"|g' composer.json

# Install dependencies
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY order-service/ .

# Complete composer setup
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]

