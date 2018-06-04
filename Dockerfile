FROM php:7.0-apache

COPY www/ /opt/ldap_user_manager
COPY entrypoint /usr/local/bin/entrypoint

RUN apt-get update && apt-get install -y --no-install-recommends libldb-dev libldap2-dev && rm -rf /var/lib/apt/lists/* && ln -s /usr/lib/x86_64-linux-gnu/libldap.so /usr/lib/libldap.so \
&& ln -s /usr/lib/x86_64-linux-gnu/liblber.so /usr/lib/liblber.so
RUN docker-php-source extract && docker-php-ext-install -j$(nproc) ldap && docker-php-source delete

RUN chmod a+x /usr/local/bin/entrypoint
RUN a2enmod rewrite ssl
RUN a2dissite 000-default default-ssl

EXPOSE 80
EXPOSE 443

CMD ["apache2-foreground"]
ENTRYPOINT ["/usr/local/bin/entrypoint"]
