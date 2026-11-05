FROM php:8.2-apache

# Install PDO MySQL and required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_sqlite fileinfo \
    && a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Create necessary directories and set write permissions
RUN mkdir -p /var/www/html/sessions /var/www/html/images/uploads /var/www/html/database \
    && chown -R www-data:www-data /var/www/html/sessions /var/www/html/images/uploads /var/www/html/database \
    && chmod -R 775 /var/www/html/sessions /var/www/html/images/uploads /var/www/html/database

# Configure Apache document root & port for Render
ENV PORT=10000
EXPOSE 10000

# Script to initialize database and adjust Apache port
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-available/000-default.conf

CMD ["apache2-foreground"]
