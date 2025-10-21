FROM serversideup/php:8.4-fpm-nginx


USER root

# Install the intl extension with root permissions
RUN install-php-extensions intl
RUN install-php-extensions gd
RUN install-php-extensions bcmath
RUN install-php-extensions sockets
RUN install-php-extensions pcov

COPY zzz-custom-php.ini /usr/local/etc/php/conf.d/

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -f \
    && apt-get install -y git default-mysql-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID
RUN docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

USER www-data
