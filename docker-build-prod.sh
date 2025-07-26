#!/bin/bash

set -e

export HOST_UID=$(id -u)

# Set variables from docker.env
set -a && source backend/.env && set +a

docker-compose -f docker-compose-prod.yaml down -v

docker-compose -f docker-compose-prod.yaml up --no-start --build

docker-compose -f docker-compose-prod.yaml run --rm php /bin/bash -c 'composer install'

docker-compose -f docker-compose-prod.yaml run --rm frontend /bin/sh -c "npm i"

docker-compose -f docker-compose-prod.yaml start

docker-compose -f docker-compose-prod.yaml exec php php bin/console doctrine:migrations:migrate --no-interaction --env=prod
