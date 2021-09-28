FROM php:7.4-fpm

RUN usermod -u 1000 www-data

RUN apt-get update && \
    apt-get install -y \
    libzip-dev \
    procps \
    ffmpeg \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxpm-dev \
    libfreetype6-dev \
    cron \
    wkhtmltopdf \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    beanstalkd \
    supervisor \
    git \
    wget \
    libicu-dev \
    nano \
    libpq-dev

RUN docker-php-ext-install zip && \
    docker-php-ext-install gd && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl && \
    docker-php-ext-install pdo_mysql && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install pdo && \
    docker-php-ext-install pdo_pgsql && \
    docker-php-ext-configure gd \
    --with-jpeg \
    --with-freetype

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php --filename=composer \
    && php -r "unlink('composer-setup.php');" \
    && mv composer /usr/local/bin/composer

RUN pecl install xdebug-2.9.6  \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.var_display_max_depth = -1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.var_display_max_children = -1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.var_display_max_data = -1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN pecl install redis-4.3.0 \
    && docker-php-ext-enable redis

RUN pecl install mongodb \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongo.ini

RUN wget https://get.symfony.com/cli/installer -O - | bash && \
    mv /root/.symfony/bin/symfony /usr/local/bin/symfony

COPY app/ /var/www/app/

WORKDIR /var/www/app/

COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/conf.d/ /etc/supervisor/conf.d/
COPY docker/php/ /usr/local/etc/php/conf.d/

#COPY ./docker/cron/scheduler /etc/cron.d/scheduler
#RUN crontab /etc/cron.d/scheduler

RUN echo "alias ll='ls -lah --color=auto'" >> /root/.bashrc && \
    echo "alias gs='git status'" >> /root/.bashrc

ENV PATH="/var/www/app/vendor/bin:/bin:${PATH}"

CMD ["/usr/bin/supervisord"]
