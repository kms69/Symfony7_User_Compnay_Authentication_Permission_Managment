# Project Setup Instructions

## Setup

1. **Install Composer Dependencies:**

    ```bash
    composer install
    ```

2. **Database Setup:**

   a. Set up your database configuration in the `.env` file.

   b. Create the database:

    ```bash
    php bin/console doctrine:database:create
    ```

   c. Run migrations to create database schema:

    ```bash
    php bin/console doctrine:migrations:migrate
    ```

   d. (Optional) Load database fixtures:

    ```bash
    php bin/console doctrine:fixtures:load
    ```
   ## Generating JWT Secret Key

To generate a random JWT secret key, you can use the following command in your terminal:

```bash
php SecretGenerate.php
 ```
## Running Tests

To run PHPUnit tests:

```bash
php vendor/bin/phpunit
```
