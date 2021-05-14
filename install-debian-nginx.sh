#!/bin/bash
# Script to LDAP User Manager with nginx web server on Debian 10
## Version: 1.0
# Date of update: 14/05/2021

# OS Configuration : Debian 10 Buster

### Update

apt update && apt dist-upgrade -y

### Install dependencies

apt install -y --no-install-recommends libldb-dev libldap2-dev libfreetype6-dev libjpeg-dev libpng-dev

### Install Nginx

apt install nginx nginx-extras

### Install PHP

apt install php php-fpm php-gd php-ldap

### Download and Install PHPMailer

wget https://github.com/PHPMailer/PHPMailer/archive/v6.2.0.tar.gz
tar -xzf v6.2.0.tar.gz -C /opt && mv /opt/PHPMailer-6.2.0 /opt/PHPMailer
rm v6.2.0.tar.gz

### Download and Install LDAP User Manager

wget https://github.com/wheelybird/ldap-user-manager/archive/refs/heads/master.zip
apt install unzip
unzip master.zip && rm master.zip
cp -R ldap-user-manager-master/www/ /var/www/html/ldap-user-manager/
rm -R ldap-user-manager-master
chown -R www-data:www-data /var/www/html/ldap-user-manager

### Configuration de Nginx 

cp /etc/nginx/sites-available/default /etc/nginx/sites-available/default.bak
PHP_version=$(php -v | cut -c5-7 | head -n 1)

echo -e "server {
\tlisten 80 default_server;
\tlisten [::]:80 default_server;

\troot /var/www/html;

\t# Add index.php to the list if you are using PHP
\tindex index.html index.php index.htm index.nginx-debian.html;

\tserver_name _;

\tlocation / {
\t\t# First attempt to serve request as file, then
\t\t# as directory, then fall back to displaying a 404.
\t\ttry_files \$uri \$uri/ =404;
\t}

\t# pass PHP scripts to FastCGI server
\t#
\tlocation ~ \.php$ {
\t\tinclude snippets/fastcgi-php.conf;

\t#       # With php-fpm (or other unix sockets):
\t\tfastcgi_pass unix:/run/php/php$PHP_version-fpm.sock;
\t}

\tlocation /ldap-user-manager {
\t\talias /var/www/ldap-user-manager;
\t\ttry_files \$uri \$uri/ @lum;

\t\t# deny access to .htaccess files, if Apache's document root
\t\t# concurs with nginx's one
\t\t#
\t\tlocation ~ /\.ht {
\t\t\tdeny all;
\t\t}

\t\tlocation ~ \.php$ {
\t\t\tinclude snippets/fastcgi-php.conf;
\t\t\tfastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
\t\t\tfastcgi_param SCRIPT_FILENAME $request_filename;
\t\t\tinclude /etc/nginx/lum.nginx.conf;
\t\t }
\t}
\tlocation @lum {
\t\trewrite /ldap-user-manager/(.*)$ /ldap-user-manager/index.php?/\$1 last;
\t}
}
">/etc/nginx/sites-available/default2

## Configuration of LDAP User Manager
echo "What are your settings?"
read -p "Hostname/URL of your serveur without http:// nor https:// (example : example.com) : " YOUR_URL
read -p "The URI of the LDAP server, e.g. ldap://ldap.example.com or ldaps://ldap.example.com" YOUR_LDAP_URI
read -p " The base DN for your organisation, e.g. dc=example,dc=com" YOUR_LDAP_BASE_DN
read -p "The DN for the user with permission to modify all records under LDAP_BASE_DN, e.g. cn=admin,dc=example,dc=com" YOUR_LDAP_ADMIN_BIND_DN
read -p "The password for LDAP_ADMIN_BIND_DN" YOUR_LDAP_ADMIN_BIND_PWD
read -p "The name of the group used to define accounts that can use this tool to manage LDAP accounts. e.g. admins" YOUR_LDAP_ADMINS_GROUP
echo -e "
fastcgi_param   HTTP_HOST       $YOUR_URL/ldap-user-manager;
fastcgi_param   LDAP_URI        $YOUR_LDAP_URI;
fastcgi_param   LDAP_BASE_DN    $YOUR_LDAP_ADMIN_BIND_DN;
fastcgi_param   LDAP_ADMIN_BIND_DN      $YOUR_LDAP_ADMIN_BIND_DN;
fastcgi_param   LDAP_ADMIN_BIND_PWD     $YOUR_LDAP_ADMIN_BIND_PWD;
fastcgi_param   LDAP_ADMINS_GROUP       $YOUR_LDAP_ADMINS_GROUP;
">/etc/nginx/lum.nginx.conf
service nginx reload

