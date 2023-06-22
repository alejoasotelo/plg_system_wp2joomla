FROM joomla:4.3.1

RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN touch /tmp/xdebug.log && chmod 777 /tmp/xdebug.log