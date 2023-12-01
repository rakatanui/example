# Test task for CV

## Table of Contents
- [Local Run](#local-run)
- [Documentation](#documentation)
- [Testing](#testing)
- [License](#license)
- [Contact Information](#contact-information)

## Local Run
**Important!** To enable local execution, you need to have Docker installed locally, or Docker Desktop (depending on your operating system).

To run the project locally, navigate to the .docker/docker-compose directory and execute the 'docker-compose up' command:

```bash
cd .docker/docker-compose
```
```bash
docker-compose up
```

If the command runs without errors, after all the containers have started, you need to perform database migration and populate it with test users.

To do this, you need to enter the php container:

```bash
docker exec -it ex-php bash
```
In this case, 'cn-php' is the name of the required container. If nothing has been changed in the docker-compose.yml file, entering the correct container should proceed without any issues.

Inside the container, rename .env.example file
```bash
cp .env.example .env
```

run composer install to install the dependencies
```bash
composer install
```

perform the database migration:
```bash
php bin/console doctrine:database:create
```
```bash
php bin/console doctrine:migrations:migrate
```

After completing all migrations, run the user table population with fixtures:
```bash
symfony console doctrine:fixtures:load
```

Run command for creating jwt keys (for security? please? change pass JWT_PASSPHRASE into .env)
```bash
php bin/console lexik:jwt:generate-keypair
```

## Documentation
You can explore the documentation by exporting the request collection for Postman from the .docs directory (for convenience, a collection with local environment variables is provided) or by using the swagger.yml file located in the same directory.

## Testing
The application is covered with unit tests written using phpUnit. To start tests, run the command from the container:
```bash
cd /var/www
```
```bash
vendor/bin/phpunit
```

## License
This project is licensed under the GNU GPLv3 - see the LICENSE file for details.

## Contact Information
If you have questions or need assistance, you can reach out to me (Andrei Krauchanka) via email at rakatanui@outlook.com.