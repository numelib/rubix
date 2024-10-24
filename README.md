# Rubix

CRM for non profit organization

## Table of contents

- [Requirements](#requirements)
- [Development stack summary](#development-stack-summary)
- [Installation](#installation)
  - [Modify DocumentRoot directive in Apache if needed](#modify-documentroot-directive-in-apache-if-needed)
  - [Dependancy installation](#dependancy-installation)
  - [Environment variables](#environment-variables)
  - [Database migration](#database-migration)
  - [Create the Admin user](#create-the-admin-user)


## Requirements

- Symfony CLI
- Composer
- PHP 8.2+
- Apache

## Development stack summary

- Symfony 6.4
- PHP 8.2
- EasyAdminBundle 4.10
- Javascript native
- CSS native

## Installation

### Modify DocumentRoot directive in Apache if needed

First, **modify** `DocumentRoot` **directive in Apache**. The path must point to the `public` directory of the project in order to run the `index.php` file entrypoint file in that folder.

### Dependancy installation

Next, install dependancies using Composer by running the command : 

```bash
composer install
```

### Environment variables

Then you'll have to set environment variables. A example file can be found in the root directory => `.env.local.exemple`

Copy that file, change his name to `.env.local` and modify it to your needs. Informations about each environment variable can be found in comments in that file.

### Database migration

Once this is done, you should be able to initialize the database and define his schema. For that, use the following commands :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force  

# or

php bin/console d:d:c
php bin/console d:s:u --force  
```

### Create the Admin user

In order to create an Admin user and access the application, you must create a new hashed password that you will insert in the database later. For that simply run the command : 

```bash
symfony console security:hash-password
```
Copy the generated password and execute this SQL query with phpmyadmin or directly with mysql :

NB : Don't forget to replace `hashed_password` by the password Symfony generated for you.

```sql
INSERT INTO user (username, roles, password) VALUES ('admin', '[\"ROLE_ADMIN\"]', 'hashed_password')"
```

Now, you should be able to connect to the applicatiion using the username `admin` and the password you entered with the `symfony console security:hash-password` command !