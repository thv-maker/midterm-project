# Stage 1: Base PHP image
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libmariadb-dev-compat \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Configure Apache for Symfony
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true && \
    a2enmod mpm_prefork rewrite

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install PHP dependencies (allow superuser and skip scripts)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Create necessary directories with proper permissions
RUN mkdir -p var/cache var/log public/bundles \
    && chown -R www-data:www-data var public \
    && chmod -R 775 var public

# Configure Apache VirtualHost for Symfony
RUN echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /app/public\n\
    <Directory /app/public>\n\
        Options FollowSymlinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    <FilesMatch \.php$>\n\
        SetHandler application/x-httpd-php\n\
    </FilesMatch>\n\
    ErrorLog ${APACHE_LOG_DIR}/symfony_error.log\n\
    CustomLog ${APACHE_LOG_DIR}/symfony_access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Enable the default site
RUN a2ensite 000-default

# Expose port 80
EXPOSE 80

# Run migrations and start Apache
CMD ["sh", "-c", "php bin/console doctrine:migrations:migrate --no-interaction && apache2-foreground"]
