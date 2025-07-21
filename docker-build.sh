#!/bin/bash

set -e

# Set variables from docker.env
set -a && source backend/.env && set +a

docker-compose down -v

docker-compose up --no-start --build

docker-compose run --rm php /bin/bash -c 'composer install'

docker-compose run --rm frontend /bin/sh -c "npm i"

docker-compose start

# docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction