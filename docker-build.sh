#!/bin/bash

set -e

export UID=$(id -u)

# Set variables from docker.env
set -a && source backend/.env && set +a

docker-compose down -v

docker-compose up --no-start --build

docker-compose run --rm php /bin/bash -c 'composer install'

docker-compose run --rm frontend /bin/sh -c "npm i"

docker-compose start

docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

docker-compose run --rm -e DATABASE_URL="postgresql://app:!ChangeMe!@database_test:5432/app?serverVersion=16&charset=utf8" php php bin/console doctrine:migrations:migrate --no-interaction