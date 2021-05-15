#!/bin/bash
# Script to install LDAP User Manager with nginx web server on Debian 10
## Version: 1.0
## Date of update: 14/05/2021
## By Thatoo
## OS Configuration : Debian 10 Buster

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

### Configuration of LDAP User Manager
echo "What are your settings?"
read -p "Hostname/URL of your serveur without http:// nor https:// (example : example.com) : " YOUR_URL
read -p "If you want LDAP User Manager to be accessible in a subfolder (example : example.com/subfolder) please write it here, if not, juste click enter : " YOUR_SUBFOLDER
read -p "The URI of the LDAP server, e.g. ldap://ldap.example.com or ldaps://ldap.example.com : " YOUR_LDAP_URI
read -p " The base DN for your organisation, e.g. dc=example,dc=com : " YOUR_LDAP_BASE_DN
read -p "The DN for the user with permission to modify all records under LDAP_BASE_DN, e.g. cn=admin,dc=example,dc=com : " YOUR_LDAP_ADMIN_BIND_DN
read -p "The password for LDAP_ADMIN_BIND_DN : " YOUR_LDAP_ADMIN_BIND_PWD
read -p "The name of the group used to define accounts that can use this tool to manage LDAP accounts. e.g. admins : " YOUR_LDAP_ADMINS_GROUP


### Configuration de Nginx 

rm /etc/nginx/sites-available/default
PHP_version=$(php -v | cut -c5-7 | head -n 1)

if [ $YOUR_SUBFOLDER = ""]
then
echo -e "server {
\tlisten 80 default_server;
\tlisten [::]:80 default_server;

\troot /var/www/html/ldap-user-manager;

\tindex index.html index.php index.htm index.nginx-debian.html;

\tserver_name _;

\tlocation / {
\t\ttry_files \$uri \$uri/ =404;
\t}

\tlocation ~ \.php$ {
\t\tinclude snippets/fastcgi-php.conf;
\t\tfastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
\t\tfastcgi_param SCRIPT_FILENAME \$request_filename;
\t\tinclude /etc/nginx/lum.nginx.conf;
\t}
        
\tlocation ~ /\.ht {
\t\tdeny all;
\t}
}
">/etc/nginx/sites-available/default
echo -e "fastcgi_param   LDAP_URI        $YOUR_LDAP_URI;
fastcgi_param   LDAP_BASE_DN    $YOUR_LDAP_ADMIN_BIND_DN;
fastcgi_param   LDAP_ADMIN_BIND_DN      $YOUR_LDAP_ADMIN_BIND_DN;
fastcgi_param   LDAP_ADMIN_BIND_PWD     $YOUR_LDAP_ADMIN_BIND_PWD;
fastcgi_param   LDAP_ADMINS_GROUP       $YOUR_LDAP_ADMINS_GROUP;
">/etc/nginx/lum.nginx.conf
else
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

\tlocation /$YOUR_SUBFOLDER {
\t\talias /var/www/html/$YOUR_SUBFOLDER;
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
\t\t\tfastcgi_param SCRIPT_FILENAME \$request_filename;
\t\t\tinclude /etc/nginx/lum.nginx.conf;
\t\t }
\t}
\tlocation @lum {
\t\trewrite /$YOUR_SUBFOLDER/(.*)$ /$YOUR_SUBFOLDER/index.php?/\$1 last;
\t}
}
">/etc/nginx/sites-available/default

echo -e "fastcgi_param   HTTP_HOST       $YOUR_URL/$YOUR_SUBFOLDER;
fastcgi_param   HTTP_SUBFOLDER        $YOUR_SUBFOLDER;
fastcgi_param   LDAP_URI        $YOUR_LDAP_URI;
fastcgi_param   LDAP_BASE_DN    $YOUR_LDAP_ADMIN_BIND_DN;
fastcgi_param   LDAP_ADMIN_BIND_DN      $YOUR_LDAP_ADMIN_BIND_DN;
fastcgi_param   LDAP_ADMIN_BIND_PWD     $YOUR_LDAP_ADMIN_BIND_PWD;
fastcgi_param   LDAP_ADMINS_GROUP       $YOUR_LDAP_ADMINS_GROUP;
">/etc/nginx/lum.nginx.conf
fi
service nginx reload

### Download and Install LDAP User Manager

wget https://github.com/wheelybird/ldap-user-manager/archive/refs/heads/master.zip
apt install unzip
unzip master.zip && rm master.zip
if [ $YOUR_SUBFOLDER = ""]
then
cp -R ldap-user-manager-master/www/ /var/www/html/ldap-user-manager/
chown -R www-data:www-data /var/www/html/ldap-user-manager
else
cp -R ldap-user-manager-master/www/ /var/www/html/$YOUR_SUBFOLDER/
chown -R www-data:www-data /var/www/html/$YOUR_SUBFOLDER
rm -R ldap-user-manager-master
