FROM dunglas/frankenphp:1.2.5-php8.3.13-alpine
LABEL authors="emeric"

# Use build arguments to pass host user info
ARG USERNAME=appuser
ARG USER_UID=1000
ARG USER_GID=$USER_UID

# Set build arguments as environment variables
ENV USERNAME=${USERNAME}
ENV USER_UID=${USER_UID}
ENV USER_GID=${USER_GID}

# Install Node.js, npm and other dependencies
RUN apk add --no-cache \
    nodejs \
    npm \
    git \
    curl

# Add the php-extension-installer to make life easier
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install PHP extensions as root
RUN install-php-extensions \
    pdo pdo_pgsql pgsql \
    intl gd zip opcache bcmath pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create group and user
RUN addgroup -g $USER_GID $USERNAME \
    && adduser -D -u $USER_UID -G $USERNAME $USERNAME \
    # Add ability to bind to ports
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

# Need to be able to set some values for Caddy
RUN chown -R ${USER_UID}:${USER_GID} /data/caddy && chown -R ${USER_GID}:${USER_GID} /config/caddy

# Set working directory
WORKDIR /app

# Copy and install composer dependencies as root
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Install Octane AVANT de copier l'app
RUN composer require laravel/octane --no-scripts

# Copy package.json and install npm dependencies
COPY package.json package-lock.json ./
RUN npm ci

# Copy application
COPY . .
RUN composer dump-autoload --optimize

# Install Octane config
RUN php artisan octane:install --server=frankenphp --no-interaction

# Fix permissions
RUN chown -R ${USER_UID}:${USER_GID} /app
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Tell docker that all future commands should run as the user we've created
USER $USERNAME

EXPOSE 80 443 5173

# Start with Octane FrankenPHP
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]