# Dupay Laravel backend

This repository contains the Laravel application source and public static assets. Runtime uploads, logs, dependency directories, and production credentials are intentionally excluded.

## Local setup

1. Install the PHP and Node dependencies with `composer install` and `npm install`.
2. Create a local `.env` from `.env.example` and configure the database, mail, and payment providers. Never commit the completed `.env`.
3. Run `php artisan key:generate` for a new environment. Do not replace the key on an existing deployment because encrypted data may become unreadable.
4. Create writable `storage` and `bootstrap/cache` directories, then run the required migrations after backing up the database.

## Production deployment

Deploy a reviewed commit to the server. Keep production `.env`, uploaded files, and provider credentials on the server. Install dependencies, run approved migrations, then run `php artisan config:cache` and `php artisan route:cache` after checking the configured database and mail settings. Verify admin NGN, USD, and EUR adjustments with small test amounts and confirm balances and transaction records in both web and mobile clients.
