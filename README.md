# to install this project, follow this steps:

```bash

cd /your/directory
git clone - .
docker compose -f docker/docker-compose.yml up -d --build
docker exec -it xm-php-container bash
composer install