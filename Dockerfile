FROM php:8.4-cli

# Install system deps commonly needed by composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Show versions for debug
RUN php -v && composer --version
