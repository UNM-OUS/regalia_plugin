FROM ubuntu:22.04

# install php 8.1 and other fundamental packages
RUN apt update && export DEBIAN_FRONTEND=noninteractive; apt install -y --no-install-recommends php8.1 php-curl git openssl unzip

# install the PHP extensions that basically all PHP projects should need
RUN apt update && export DEBIAN_FRONTEND=noninteractive; apt install -y php-opcache php-xdebug php-mbstring php-pdo php-zip php-gd php-xml

# install extensions that are more project-specific
RUN apt update && export DEBIAN_FRONTEND=noninteractive; apt install -y php-sqlite3 cron

# install composer and its CA certificates
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY --from=composer:latest /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt

# change INI settings
RUN sed -i 's,^post_max_size =.*$,post_max_size = 300M,' /etc/php/8.1/cli/php.ini
RUN sed -i 's,^upload_max_filesize =.*$,upload_max_filesize = 300M,' /etc/php/8.1/cli/php.ini

# copy global config
COPY global/global.yaml /workspace/