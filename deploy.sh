#!/bin/bash

set -e

git pull origin master

docker-compose -f docker-compose-prod.yaml run --rm php /bin/bash -c 'composer install'

docker-compose -f docker-compose-prod.yaml run --rm frontend /bin/sh -c "npm i"

docker-compose -f docker-compose-prod.yaml run --rm frontend /bin/sh -c "npm run build"

docker-compose -f docker-compose-prod.yaml exec php php bin/console doctrine:migrations:migrate --no-interaction --env=prod

docker-compose -f docker-compose-prod.yaml exec php php bin/console cache:clear --env=prod
