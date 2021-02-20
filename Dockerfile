FROM php:7.0-apache

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libldb-dev libldap2-dev \
        libfreetype6-dev \
        libjpeg-dev \
        libpng-dev && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
         --enable-gd-native-ttf \
         --with-freetype-dir=/usr/include/freetype2 \
         --with-png-dir=/usr/include \
         --with-jpeg-dir=/usr/include && \
    docker-php-ext-install -j$(nproc) gd && \
    docker-php-ext-configure ldap --with-libdir=lib/`uname -m`-linux-gnu/ && \
    docker-php-ext-install -j$(nproc) ldap

ADD https://github.com/PHPMailer/PHPMailer/archive/v6.2.0.tar.gz /tmp

RUN a2enmod rewrite ssl
RUN a2dissite 000-default default-ssl

EXPOSE 80
EXPOSE 443

COPY www/ /opt/ldap_user_manager
RUN tar -xzf /tmp/v6.2.0.tar.gz -C /opt && mv /opt/PHPMailer-6.2.0 /opt/PHPMailer

COPY entrypoint /usr/local/bin/entrypoint
RUN chmod a+x /usr/local/bin/entrypoint

CMD ["apache2-foreground"]
ENTRYPOINT ["/usr/local/bin/entrypoint"]
