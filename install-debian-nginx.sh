# Tuto Install Debian LDAP User Manager with nginx

### Install debian buster container and update

```
apt update && apt dist-upgrade -y
```

### Install dependencies

```
apt install -y --no-install-recommends libldb-dev libldap2-dev libfreetype6-dev libjpeg-dev libpng-dev
```

### Install Nginx

```
apt install nginx nginx-extras
```

### Install PHP

```
apt install php php-fpm php-gd php-ldap
```

### Download and Install PHPMailer

```
wget https://github.com/PHPMailer/PHPMailer/archive/v6.2.0.tar.gz
tar -xzf v6.2.0.tar.gz -C /opt && mv /opt/PHPMailer-6.2.0 /opt/PHPMailer
rm v6.2.0.tar.gz
```

### Download and Install LDAP User Manager

```
wget https://github.com/wheelybird/ldap-user-manager/archive/refs/heads/master.zip
apt install unzip
unzip master.zip && rm master.zip
cp -R ldap-user-manager-master/www/ /var/www/html/ldap-user-manager/
rm -R ldap-user-manager-master
chown -R www-data:www-data /var/www/html/ldap-user-manager
```

### Configuration de Nginx 

```
cp /etc/nginx/sites-available/default /etc/nginx/sites-available/default.bak
PHP_version=$(php -v | cut -c5-7 | head -n 1)
```

```
server {
\tlisten 80 default_server;
\tlisten [::]:80 default_server;

\troot /var/www/html/ldap_user_manager;

\t# Add index.php to the list if you are using PHP
\tindex index.html index.php index.htm index.nginx-debian.html;

\tserver_name _;

\tlocation / {
\t\t# First attempt to serve request as file, then
\t\t# as directory, then fall back to displaying a 404.
\t\ttry_files $uri $uri/ =404;
\t}

\t# pass PHP scripts to FastCGI server
\t#
\tlocation ~ \.php$ {
\t\tinclude snippets/fastcgi-php.conf;

\t#       # With php-fpm (or other unix sockets):
\t\tfastcgi_pass unix:/run/php/php$PHP_version-fpm.sock;
\t\tinclude /etc/nginx/lum.nginx.conf;
\t}

\t# deny access to .htaccess files, if Apache's document root
\t# concurs with nginx's one
\t#
\tlocation ~ /\.ht {
\t\tdeny all;
\t}
}
">/etc/nginx/sites-available/default
```

```
service nginx reloadnano /etc/nginx/lum.nginx.conf
```

