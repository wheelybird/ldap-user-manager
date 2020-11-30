LDAP User Manager
--

This is a PHP LDAP account manager; a web-based GUI interface which allows you to quickly populate a new LDAP directory and easily manage user accounts and groups.  It also has a self-service password change module.   
It's designed to work with OpenLDAP and to be run as a container.  It complements OpenLDAP containers such as [*osixia/openldap*](https://hub.docker.com/r/osixia/openldap/).



Features
---

 * Setup wizard: this will create the necessary structure to allow you to add users and groups and will set up an initial admin user that can log into the user manager.
 * Group creation and management.
 * User account creation and management.
 * Optionally send an email to the new user with their account credentials.
 * Secure password auto-generator: click the button to generate a secure password.
 * Password strength indicator.
 * Self-service password change: non-admin users can log in to change their password.

Screenshots
---

**Initial setup: add an administrator account**:   
![administrator_setup](https://user-images.githubusercontent.com/17613683/59344224-8bb8ae80-8d05-11e9-869b-d08a44f4939d.png)

**Add a new group**:   
![new_group](https://user-images.githubusercontent.com/17613683/59344242-95421680-8d05-11e9-9a72-1f55c06dd43d.png)

**Manage group membership**:   
![group_membership](https://user-images.githubusercontent.com/17613683/59344247-97a47080-8d05-11e9-8606-0bcc40471458.png)

**Edit accounts**:   
![account_overview](https://user-images.githubusercontent.com/17613683/59344255-9c692480-8d05-11e9-9207-051291bafd91.png)

**Self-service password change**:   
![self_service_password_change](https://user-images.githubusercontent.com/17613683/59344258-9ffcab80-8d05-11e9-9dc2-27dfd373fcc8.png)


A note on your LDAP schema - please read this!
---

By default this application will expect the LDAP server to be using the **RFC2307BIS** schema.  OpenLDAP (including the **osixia/openldap** image) uses the older NIS schema as its default schema.   

> :warning: If you haven't explicitly set up the **RFC2307BIS** schema on your LDAP server then you need to set `LDAP_USES_NIS_SCHEMA` to `TRUE` as shown in the Quick start example below.

**Why should I use RFC2307BIS?**   

The user manager will work with either NIS or BIS, but BIS is recommended as it allows you to use **memberOf** searches.  You can enable BIS in **osixia/openldap** by setting `LDAP_RFC2307BIS_SCHEMA` to `true` during the initial setup.

**Why not make NIS the default?**   

The original versions of this application were set to expect BIS by default.  This was before we used specific release versions, so we're expecting the BIS schemas as the default to keep backwards-compatibility for anyone using the `latest` tag.

**I'm unable to use the BIS schema!**   

If you can't or prefer not to use RFC2307BIS then set `LDAP_USES_NIS_SCHEMA` to `TRUE`.  This will create groups solely as the **posixGroup** objectclass, and the default for `LDAP_GROUP_MEMBERSHIP_USES_UID` will `TRUE`.


Quick start
---

```
docker run \
           --detach \
           --name=lum \
           -p 80:80 \
           -p 443:443 \
           -e "SERVER_HOSTNAME=lum.example.com" \
           -e "LDAP_URI=ldap://ldap.example.com" \
           -e "LDAP_BASE_DN=dc=example,dc=com" \
           -e "LDAP_REQUIRE_STARTTLS=TRUE" \
           -e "LDAP_ADMINS_GROUP=admins" \
           -e "LDAP_ADMIN_BIND_DN=cn=admin,dc=example,dc=com" \
           -e "LDAP_ADMIN_BIND_PWD=secret"\
           -e "LDAP_USES_NIS_SCHEMA=true" \
           -e "EMAIL_DOMAIN=ldapusermanager.org"\
           wheelybird/ldap-user-manager:v1.4
```
Change the variable values to suit your environment.  You might need to change `LDAP_USES_NIS_SCHEMA` if you're using the BIS schema. Now go to https://lum.example.com/setup.


Configuration
---

Configuration is via environmental variables.  Please bear the following in mind:

 * This tool needs to bind to LDAP as a user with permissions to modify everything under the base DN.
 * This interface is designed to work with a fresh LDAP server and should be against populated LDAP directories with caution and at your own risk.

Mandatory:
----

* `LDAP_URI`:  The URI of the LDAP server, e.g. `ldap://ldap.example.com` or `ldaps://ldap.example.com`
   
* `LDAP_BASE_DN`:  The base DN for your organisation, e.g. `dc=example,dc=com`
   
* `LDAP_ADMIN_BIND_DN`: The DN for the user with permission to modify all records under `LDAP_BASE_DN`, e.g. `cn=admin,dc=example,dc=com`
   
* `LDAP_ADMIN_BIND_PWD`: The password for `LDAP_ADMIN_BIND_DN`
   
* `LDAP_ADMINS_GROUP`: The name of the group used to define accounts that can use this tool to manage LDAP accounts.  e.g. `admins`

Optional:
----

**Organisation settings**   

* `SERVER_HOSTNAME` (default: *ldapusername.org*):  The hostname that this interface will be served from.
   
* `ORGANISATION_NAME`: (default: *LDAP*): Your organisation's name.
   
* `SITE_NAME` (default: *{ORGANISATION_NAME} user manager*):  Change this to replace the title in the menu, e.g. "My Company Account Management".


**LDAP settings**   

* `LDAP_USER_OU` (default: *people*):  The name of the OU used to store user accounts (without the base DN appended).
   
* `LDAP_USES_NIS_SCHEMA` (default: *FALSE*):  If you use the NIS schema instead of the (preferable) RFC2307BIS schema, set this to `TRUE`.  See [A note on your LDAP schema](#a-note-on-your-ldap-schema) for more information.
   
* `LDAP_GROUP_OU` (default: *groups*):  The name of the OU used to store groups (without the base DN appended).
* `LDAP_GROUP_MEMBERSHIP_ATTRIBUTE` (default: *memberUID* or *uniqueMember*):  The attribute used when adding a user to a group.  If `LDAP_USES_NIS_SCHEMA` is `TRUE` the default is `memberUID`, otherwise it's `uniqueMember`.  Explicitly setting this variable will override the default.
* `LDAP_GROUP_MEMBERSHIP_USES_UID` (default: *TRUE* or *FALSE*): If *TRUE* then the entry for a member of a group will be just the username.  Otherwise it's the member's full DN.  If `LDAP_USES_NIS_SCHEMA` is `TRUE` the default is `TRUE`, otherwise it's `FALSE`.  Explicitly setting this variable will override the default.
   
* `LDAP_REQUIRE_STARTTLS` (default: *TRUE*):  If *TRUE* then a TLS connection is required for this interface to work.  If set to *FALSE* then the interface will work without STARTTLS, but a warning will be displayed on the page.
   
* `LDAP_IGNORE_CERT_ERRORS` (default: *FALSE*): If *TRUE* then problems with the certificate presented by the LDAP server will be ignored (for example FQDN mismatches).  Use this if your LDAP server is using a self-signed certificate and you don't have a CA certificate for it or you're connecting to a pool of different servers via round-robin DNS.
   
* `LDAP_TLS_CACERT` (no default): If you need to use a specific CA certificate for TLS connections to the LDAP server (when `LDAP_REQUIRE_STARTTLS` is set) then assign the contents of the CA certificate to this variable.  e.g. `-e LDAP_TLS_CACERT=$(</path/to/ca.crt)`

**User account settings**   

* `DEFAULT_USER_GROUP` (default: *everybody*):  The group that new accounts are automatically added to when created.  *NOTE*: If this group doesn't exist then a group is created with the same name as the username and the user is added to that group.
   
* `DEFAULT_USER_SHELL` (default: */bin/bash*):  The shell that will be launched when the user logs into a server.
   
* `EMAIL_DOMAIN` (no default):  If set then the email address field will be automatically populated in the form of `username@email_domain`.
   
* `USERNAME_FORMAT` (default: *{first_name}-{last_name}*):  The template used to dynamically generate usernames.  See [Username format](#username-format).
   
* `USERNAME_REGEX` (default: *^[a-z][a-zA-Z0-9\._-]{3,32}$*): The regular expression used to ensure a username (and group name) is valid.  See [Username format](#username-format).

* `PASSWORD_HASH` (no default):  Select which hashing method which will be used to store passwords in LDAP.  Options are (in order of precedence) `SHA512CRYPT`, `SHA256CRYPT`, `MD5CRYPT`, `SSHA`, `SHA`, `SMD5`, `MD5`, `CRYPT` & `CLEAR`.  If your chosen method isn't available on your system then the strongest available method will be automatically selected - `SSHA` is the strongest method guaranteed to be available.  Cleartext passwords should NEVER be used in any situation outside of a test.
   
* `ACCEPT_WEAK_PASSWORDS` (default: *FALSE*):  Set this to *TRUE* to prevent a password being rejected for being too weak.  The password strength indicators will still gauge the strength of the password.  Don't enable this in a production environment.
   

**Email settings**

* `SMTP_HOSTNAME` (no default): The hostname of an SMTP server - used to send emails when creating new accounts.
   
* `SMTP_HOST_PORT` (default: *25*): The SMTP port on the SMTP server.
   
* `SMTP_USERNAME` (no default): The username to use when the SMTP server requires authentication.
   
* `SMTP_PASSWORD` (no default): The password to use when the SMTP server requires authentication.
   
* `SMTP_USE_TLS` (default: *FALSE*): Set to TRUE if the SMTP server requires TLS to be enabled.
   
* `EMAIL_FROM_ADDRESS` (default: *admin@{EMAIL_DOMAIN}*): The FROM email address used when sending out emails.  The default domain is taken from `EMAIL_DOMAIN` under **User account settings**.
   
* `EMAIL_FROM_NAME` (default: *{SITE_NAME}*): The FROM name used when sending out emails.  The default name is taken from `SITE_NAME` under **Organisation settings**.

**Site security settings**   

* `NO_HTTPS` (default: *FALSE*): If you set this to *TRUE* then the server will run in HTTP mode, without any encryption.  This is insecure and should only be used for testing.
   
* `LOGIN_TIMEOUT_MINS` (default: *10 minutes*):  How long before an idle session will be timed out.

**Debug settings**

* `LDAP_DEBUG` (default: *FALSE*): Set to TRUE to increase the logging level for LDAP requests.  This will output passwords to the error log - don't enable this in a production environment.  This is for information on problems updating LDAP records and such.  To debug problems connecting to the LDAP server in the first place use `LDAP_VERBOSE_CONNECTION_LOGS`.
   
* `LDAP_VERBOSE_CONNECTION_LOGS` (default: *FALSE*): Set to TRUE to enable detailed LDAP connection logs (PHP's LDAP_OPT_DEBUG_LEVEL 7).  This will flood the logs with detailled LDAP connection information so disable this for production environments.
   
* `SESSION_DEBUG` (default: *FALSE*): Set to TRUE to increase the logging level for sessions and user authorisation.  This will output cookie passkeys to the error log - don't enable this in a production environment.
   
* `SMTP_LOG_LEVEL` (default: *0*): Set to between 1-4 to get SMTP logging information (0 disables SMTP debugging logs though it will still display errors). See https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Debugging for details of the levels.
   
Webserver SSL setup
---

When `NO_HTTPS` is set to **false** (the default), the webserver (Apache HTTPD) expects to find `/opt/ssl/server.key` and `/opt/ssl/server.crt`, and these certificates should match `SERVER_HOSTNAME`.  If these files aren't found then the startup script will create self-signed certificates based on `SERVER_HOSTNAME`.  To use your own key and certificate then you need to bind-mount a directory containing them to `/opt/ssl`.  The script will also look for `/opt/ssl/chain.pem` if you need to add a certificate chain file (the Apache `SSLCertificateChainFile` option).
   
e.g.:
```
docker run \
           --detach \
           --name=lum \
           -p 80:80 \
           -p 443:443 \
           -e SERVER_HOSTNAME=lum.ldapusermanager.org \
           -v /your/ssl/cert/dir:/opt/ssl \
           ...
           ...

```

Initial setup
---

Ideally you'll be using this against an empty LDAP directory.  You can use the setup utility to create the LDAP structures that this tool needs in order to create accounts and groups.   Go to `https://_website-hostname_/setup` to get started.   You need to log in with the password for the admin user as set by `LDAP_ADMIN_BIND_DN`.   
The setup utility will create the user and account trees, records that store the last UID and GID used when creating a user account or group, a group for admins and the initial admin account.

![initial_setup](https://user-images.githubusercontent.com/17613683/59344213-865b6400-8d05-11e9-9d86-381d59671530.png)


Sending emails
---

When you create an account you'll have an option to send an email to the person you created the account for.  The email will give them their new username, password and a link to the self-service password change utility.   

Emails are sent via SMTP, so you'll need to be able to connect to an SMTP server and pass in the settings for that server via environmental variables - see **Email settings** above.   
If you haven't passed in those settings or if the account you've created has no (valid) email address then the option to send an email will be disabled.

When the account is created you'll be told if the email was sent or not but be aware that just because your SMTP server accepted the email it doesn't mean that it was able to deliver it.  If you get a message saying the email wasn't sent then check the logs for the error.  You can increase the log level (`SMTP_LOG_LEVEL`) to above 0 in order to see SMTP debug logs.

Username format
---

When entering the user's first and last names a bit of JavaScript automatically generates the username.  The way it generates is it based on a template format defined by `USERNAME_FORMAT`.  This is basically a string in which predefined macros are replaced by the formatted first and/or last name.   
The default is `{first_name}-{last_name}` with which *Jonathan Testperson*'s username would be *jonathan-testperson*.   
Currently the available macros are:

* `{first_name}` : the first name in lowercase
* `{first_name_initial}` : the first letter of the first name in lowercase
* `{last_name}`: the last name in lowercase
* `{last_name_initial}`: the first initial of the last name in lowercase

Anything else in the `USERNAME_FORMAT` string is left as defined, but the username is also checked for validity against `USERNAME_REGEX`.  This is to ensure that there aren't any characters forbidden by other systems (i.e. email or Linux/Unix accounts).

If `EMAIL_DOMAIN` is set then the email address field will be automatically updated in the form of `username@email_domain`.  Entering anything manually in that field will stop the automatic update of the email field.


Testing with an LDAP container
--

This will set up an OpenLDAP container you can use to test the user manager against.  It uses the BIS schema.  This won't be using HTTPS or TLS, so don't use this in production.
```
docker run \
             --detach \
             --restart unless-stopped \
             --name openldap \
             -e LDAP_ORGANISATION=wheelybird \
             -e LDAP_DOMAIN=wheelybird.com \
             -e LDAP_ADMIN_PASSWORD=change_me \
             -e LDAP_RFC2307BIS_SCHEMA=true \
             -e LDAP_REMOVE_CONFIG_AFTER_SETUP=true \
             -e LDAP_TLS=false \
             -p 389:389
             --volume /opt/docker/openldap/var_lib_ldap:/var/lib/ldap \
             --volume /opt/docker/openldap/etc_ldap_slapd.d:/etc/ldap/slapd.d \
             osixia/openldap:latest
             
docker run \
             --detach \
             --name=lum \
             -p 80:80 \
             -e SERVER_HOSTNAME=localhost \
             -e LDAP_URI=ldap://172.17.0.1 \
             -e LDAP_BASE_DN=dc=wheelybird,dc=com \
             -e LDAP_ADMINS_GROUP=admins \
             -e LDAP_ADMIN_BIND_DN="cn=admin,dc=wheelybird,dc=com" \
             -e LDAP_ADMIN_BIND_PWD=change_me \
             -e NO_HTTPS=TRUE \
             wheelybird/ldap-user-manager:latest
```
Now go to http://localhost/setup - the password is `change_me` (unless you changed it).
