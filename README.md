Setup Adnetwork Project (Backend - Laravel)



Environment Requirement:

Composer

Apache server (PHP 8.2+)

Mysql server

Steps:

Clone the project repository

https://github.com/rashu-pro/ad-network-api.git





Create the .env configuration file

.env



Generate the application encryption key
php artisan key:generate



Install PHP dependencies via Composer
composer install



Run database migrations to create tables
php artisan migrate



Seed the database with initial data
php artisan db:seed



Run the project

php artisasn serve 
