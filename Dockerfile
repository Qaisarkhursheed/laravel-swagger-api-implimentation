FROM php:8.2-fpm-alpine

# Install Linux kernel headers
RUN apk --no-cache add \
    linux-headers

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql sockets

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
     --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY . .

# # Run Composer install
# RUN composer install


# Run Composer install
# RUN mkdir /app/vendor
RUN chmod -R 777 /app/storage
RUN chmod -R 777 /app/
# RUN chmod -R 777 /app/vendor
RUN composer install --verbose
RUN composer dump-autoload

RUN chown -R www-data:www-data /app/storage
# Generate Laravel application key
RUN php artisan key:generate
# Clear the  cache
RUN php artisan config:cache
RUN php artisan config:clear
RUN php artisan optimize
# Clear the route cache
RUN php artisan route:list
# Generates the new swagger docs
RUN php artisan l5-swagger:generate

