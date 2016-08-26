FROM mariadb:latest

ARG HOST_USER_ID=1000
# update UID for volume permissions
RUN usermod -u $HOST_USER_ID mysql
