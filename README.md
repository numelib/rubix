# rubix
CRM for non profit organization / CRM dédié aux associations

## Requirements

- Symfony CLI
- Composer
- PHP 8.2+
- Apache

## Installation

### Modify DocumentRoot in Apache

DocumentRoot directive path must point to the `public` directory of the project

### Dependancy installation

composer install

### Admin User

symfony console security:hash-password

Get the generated password

Go into your phpmyadmin install or execute this statement directly with mysql :

INSERT INTO admin (id, username, roles, password) VALUES ('admin', '[\"ROLE_ADMIN\"]', 'hashed_password')"

Replace "hashed_password" by the hashed password Symfony generated

### Environment variables

See .env.local.exemple

Setup DATABASE_URL variable to your needs

### Database migration

doctrine:database:create

doctrine:schema:update --force or d:s:u --force in short