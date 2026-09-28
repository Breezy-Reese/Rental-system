
FROM php:8.3-apache

# Enable Apache URL rewriting
RUN a2enmod rewrite

# Set Apache document root to the public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Update Apache virtual host configuration
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides
RUN printf '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/public-directory.conf \
    && a2enconf public-directory

# Copy PHP application
WORKDIR /var/www/html
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Configure Apache to use Render's assigned port
CMD ["bash", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]