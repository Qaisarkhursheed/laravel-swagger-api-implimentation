# OP5 Monitor Laravel Project

Welcome to the OP5 Monitor Laravel project! Follow the steps below to set up and run the project.

## Installation

1. Clone the repository:

    ```bash
    git clone https://github.com/your-username/op5-monitor-laravel.git
    ```

2. Change into the project directory:

    ```bash
    cd op5-monitor-laravel
    ```

3. Install dependencies using Composer:

    ```bash
    composer install
    ```

4. Copy the `.env.example` file to `.env`:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5. Update the `.env` file with your database credentials and other necessary settings:

    ```bash
    nano .env
    ```

    Set the following values:
    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=your-database-host
    DB_PORT=your-database-port
    DB_DATABASE=your-database-name
    DB_USERNAME=your-database-username
    DB_PASSWORD=your-database-password
    ```

6. Run the following Artisan commands:

    ```bash
    php artisan config:cache
    php artisan config:clear
    ```

7. Generate Swagger documentation:

    ```bash
    php artisan l5-swagger:generate
    ```

## Access API Documentation

Visit the following URL in your browser to access the API documentation:
${baseurl or ip}/api/documentation#/

