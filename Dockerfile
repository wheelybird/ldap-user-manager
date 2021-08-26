FROM php:8-apache

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libldb-dev libldap2-dev \
        libfreetype6-dev \
        libjpeg-dev \
        libpng-dev && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype && \
    docker-php-ext-install -j$(nproc) gd && \
    libdir=$(find /usr -name "libldap.so*" | sed -e 's/\/usr\///' -e 's/\/libldap.so//') && \
    docker-php-ext-configure ldap --with-libdir=$libdir && \
    docker-php-ext-install -j$(nproc) ldap

ADD https://github.com/PHPMailer/PHPMailer/archive/v6.2.0.tar.gz /tmp

RUN a2enmod rewrite ssl && a2dissite 000-default default-ssl

EXPOSE 80
EXPOSE 443

COPY www/ /opt/ldap_user_manager
RUN tar -xzf /tmp/v6.2.0.tar.gz -C /opt && mv /opt/PHPMailer-6.2.0 /opt/PHPMailer

COPY entrypoint /usr/local/bin/entrypoint
RUN chmod a+x /usr/local/bin/entrypoint

CMD ["apache2-foreground"]
ENTRYPOINT ["/usr/local/bin/entrypoint"]
