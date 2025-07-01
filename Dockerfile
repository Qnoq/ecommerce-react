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
    intl gd zip opcache bcmath \
    redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create group and user
RUN addgroup -g $USER_GID $USERNAME \
    && adduser -D -u $USER_UID -G $USERNAME $USERNAME \
    # Add ability to bind to ports
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

# Need to be able to set some values for Caddy
RUN chown -R ${USER_UID}:${USER_GID} /data/caddy && chown -R ${USER_UID}:${USER_GID} /config/caddy

# Set working directory
WORKDIR /app

# Copy and install composer dependencies as root
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy package.json and install npm dependencies
COPY package.json package-lock.json ./
RUN npm ci

# Copy application
COPY . .
RUN composer dump-autoload --optimize

# Fix permissions
RUN chown -R ${USER_UID}:${USER_GID} /app
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Créez les répertoires nécessaires pour PsySH
RUN mkdir -p /home/${USERNAME}/.config/psysh \
    && chown -R ${USER_UID}:${USER_GID} /home/${USERNAME}/.config

# Fix permissions
RUN chown -R ${USER_UID}:${USER_GID} /app
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Tell docker that all future commands should run as the user we've created
USER $USERNAME

# Définir la variable d'environnement pour PsySH
ENV PSYSH_CONFIG_DIR=/home/${USERNAME}/.config/psysh

EXPOSE 80 443 5173

# Start only FrankenPHP (Vite will run locally)
CMD ["frankenphp", "run"]