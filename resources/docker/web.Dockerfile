FROM ubuntu:latest

RUN apt-get update && apt-get -y install \
    nginx \
    php7.0-fpm \
    php7.0-mysql

# update UID for volume permissions
ARG HOST_USER_ID=1000
RUN usermod -u $HOST_USER_ID www-data

# supervisord
RUN /usr/bin/easy_install supervisor
RUN /usr/bin/easy_install supervisor-stdout
ADD ./conf/supervisord.conf /etc/supervisord.conf

COPY ./conf/vhost.conf /etc/nginx/sites-enabled/racktables.dev
RUN echo 'daemon off;' >> /etc/nginx/nginx.conf
RUN chmod 777 /var/log/nginx

RUN mkdir -p /run/php
RUN sed -i 's/;daemonize = yes/daemonize = no/' /etc/php/7.0/fpm/php-fpm.conf

EXPOSE 80 443

CMD ["/usr/local/bin/supervisord", "-n"]
