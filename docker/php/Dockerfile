FROM php:8.4-fpm

# Install system deps
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libxml2-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node 22
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring bcmath pcntl

# Create group and user
RUN groupadd -g 1000 gym_app_backend && \
    useradd -u 1000 -ms /bin/bash -g gym_app_backend gym_app_backend

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/gym-app-backend

# Copy project files with correct ownership
COPY --chown=gym_app_backend:gym_app_backend . /var/www/gym-app-backend

# ✅ Full permissions on everything
RUN chmod -R 777 /var/www/gym-app-backend && \
    chown -R gym_app_backend:gym_app_backend /var/www/gym-app-backend

# ✅ Laravel specific folders — read, write, delete, create, move
RUN mkdir -p /var/www/gym-app-backend/storage/app/public \
             /var/www/gym-app-backend/storage/framework/cache/data \
             /var/www/gym-app-backend/storage/framework/sessions \
             /var/www/gym-app-backend/storage/framework/testing \
             /var/www/gym-app-backend/storage/framework/views \
             /var/www/gym-app-backend/storage/logs \
             /var/www/gym-app-backend/bootstrap/cache && \
    chmod -R 777 /var/www/gym-app-backend/storage && \
    chmod -R 777 /var/www/gym-app-backend/bootstrap/cache && \
    chown -R gym_app_backend:gym_app_backend /var/www/gym-app-backend/storage && \
    chown -R gym_app_backend:gym_app_backend /var/www/gym-app-backend/bootstrap/cache

# Copy entrypoint
COPY --chown=gym_app_backend:gym_app_backend docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER gym_app_backend

EXPOSE 9000
CMD ["php-fpm"]
