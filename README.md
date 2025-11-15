# to install this project, follow this steps:

```bash

cd /your/directory
git clone https://github.com/dancostinel/xm.git .
docker compose -f docker/docker-compose.yml up -d --build
docker exec -it xm-php-container bash
composer install