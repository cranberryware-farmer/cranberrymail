FROM webdevops/php-apache:7.3
RUN apt-get -y update
# Install base dependencies
RUN apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev imagemagick nano net-tools iputils-ping default-mysql-client apt-transport-https build-essential ca-certificates curl git libssl-dev wget libmemcached-dev memcached libmemcached-tools redis-server
RUN rm -rf /var/lib/apt/lists/*
RUN pecl install igbinary \
    && pecl install 
RUN docker-php-ext-enable imagick \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd \
    && docker-php-ext-enable memcached \
    && docker-php-ext-enable redis \
    && docker-php-ext-enable igbinary
RUN docker-php-ext-install -j$(nproc) mysqli && docker-php-ext-enable mysqli
RUN chown -R www-data.www-data /var/www/html
RUN cd /app && composer install
# Replace shell with bash so we can source files
RUN rm /bin/sh && ln -s /bin/bash /bin/sh
# Setup NVM
ENV NVM_DIR /usr/local/nvm
ENV NODE_VERSION 10.16.0

WORKDIR $NVM_DIR

RUN curl https://raw.githubusercontent.com/creationix/nvm/v0.35.0/install.sh | bash \
    && . $NVM_DIR/nvm.sh \
    && nvm install $NODE_VERSION \
    && nvm alias default $NODE_VERSION \
    && nvm use default

ENV NODE_PATH $NVM_DIR/versions/node/v$NODE_VERSION/lib/node_modules
ENV PATH      $NVM_DIR/versions/node/v$NODE_VERSION/bin:$PATH
