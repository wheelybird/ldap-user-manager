LDAP User Manager
--

A PHP web-based interface for LDAP user account management and self-service password change.


Purpose
---

This presents a simple-to-use interface for setting up a new LDAP directory and managing user accounts and groups, as well as providing a way for users to change their own password.  It's designed to complement OpenLDAP servers such as *osixia/openldap* (https://hub.docker.com/r/osixia/openldap/).   

 * Setup wizard: this will create the necessary structure to allow you to add users and groups and will set up an initial admin user that can log into the user manager.
 * Group creation and management.
 * User account creation and management.
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
           -e "LDAP_STARTTLS=TRUE" \
           -e "LDAP_ADMINS_GROUP=admins" \
           -e "LDAP_ADMIN_BIND_DN=cn=admin,dc=example,dc=com" \
           -e "LDAP_ADMIN_BIND_PWD=secret"\
           -e "EMAIL_DOMAIN=example.com"\
           wheelybird/ldap-user-manager
```
Now go to https://lum.example.com/setup.


Configuration
---

Configuration is via environmental variables.

**Note**: This tool needs to bind to LDAP as a user with permissions to modify everything under the base DN.
**WARNING**: This interface is designed to work with a fresh LDAP server and should be used with populated LDAP directories with caution and at your own risk.

Mandatory:
----

* `LDAP_URI`:  The URI of the LDAP server.  e.g. *ldap://ldap.example.com* or *ldaps://ldap.example.com*
* `LDAP_BASE_DN`:  The base DN for your organisation.  e.g. *dc=example,dc=com`
* `LDAP_ADMIN_BIND_DN`: The DN for the user with permission to modify all records under `LDAP_BASE_DN`. e.g. `cn=admin,dc=example,dc=com`
* `LDAP_ADMIN_BIND_PWD`: The password for `LDAP_ADMIN_BIND_DN`
* `LDAP_ADMINS_GROUP`: The name of the group used to define accounts that can use this tool to manage LDAP accounts.  e.g. `admins`

Optional:
----

* `SERVER_HOSTNAME` (default: *example.com*):  The hostname that this interface will be served from.
   
* `LDAP_USER_OU` (default: *people*):  The name of the OU used to store user accounts (without the base DN appended).
   
* `LDAP_GROUP_OU` (default: *groups*):  The name of the OU used to store groups (without the base DN appended).
* `LDAP_GROUP_MEMBERSHIP_ATTRIBUTE` (default: *uniqueMember*):  The attribute used when adding a user to a group.
* `LDAP_GROUP_MEMBERSHIP_USES_UID`(default: *FALSE*): If *TRUE* then the entry for a member of a group will be just the username.  Otherwise it's the member's full DN.
   
* `LDAP_ACCOUNT_ATTRIBUTE` (default: *uid*):  The attribute used to identify account usernames.
   
* `LDAP_REQUIRE_STARTTLS` (default: *TRUE*):  If *TRUE* then a TLS connection is required for this interface to work.  If set to *FALSE* then the interface will work without STARTTLS, but a warning will be displayed on the page.
   
* `LDAP_TLS_CACERT` (no default): If you need to use a specific CA certificate for TLS connections to the LDAP server (when `LDAP_REQUIRE_STARTTLS` is set) then assign the contents of the CA certificate to this variable.  e.g. `-e LDAP_TLS_CERT=$(</path/to/ca.crt)`
   
* `DEFAULT_USER_GROUP` (default: *everybody*):  The group that new accounts are automatically added to when created.  *NOTE*: If this group doesn't exist then a group is created with the same name as the username and the user is added to that group.
* `DEFAULT_USER_SHELL` (default: */bin/bash*):  The shell that will be launched when the user logs into a server.
* `EMAIL_DOMAIN` (no default):  If set then the email address field will be automatically populated in the form of `username@email_domain`).
   
* `USERNAME_FORMAT` (default: *{first_name}-{last_name}*):  The template used to dynamically generate usernames.  See the _Usernames_ section below.
* `USERNAME_REGEX` (default: *^[a-z][a-zA-Z0-9\._-]{3,32}$*): The regular expression used to ensure a username (and group name) is valid.  See the _Usernames_ section below.
   
* `LOGIN_TIMEOUT_MINS` (default: 10 minutes):  How long before an idle session will be timed out.
   
* `SITE_NAME` (default: *LDAP user manager*):  Change this to replace the title in the menu.  e.g. "My Company"


Webserver SSL setup
---

The webserver (Apache HTTPD) expects to find `/opt/ssl/server.key` and `/opt/ssl/server.crt`, and these certificates should match `SERVER_HOSTNAME`.   
If those files aren't found then the startup script will create self-signed certificates based on `SERVER_HOSTNAME`.  To use your own key and certificate then you need to bind-mount a directory containing them to `/opt/ssl`.  The script will also look for `/opt/ssl/chain.pem` if you need to add a certificate chain file (the Apache `SSLCertificateChainFile` option).
   
e.g.:
```
docker run \
           --detach \
           --name=lum \
           -p 80:80 \
           -p 443:443 \
           -e SERVER_HOSTNAME=lum.example.com \
           -v /your/ssl/cert/dir:/opt/ssl \
           ...
           ...

```

Initial setup
---

Ideally you'll be using this against an empty LDAP directory.  You can use the setup utility to create the LDAP structures that this tool needs in order to create accounts and groups.   Go to `https://_website-hostname_/setup` to get started.   You need to log in with the password for the admin user as set by `LDAP_ADMIN_BIND_DN`.   
The setup utility will create the user and account trees, records that store the last UID and GID used when creating a user account or group, a group for admins and the initial admin account.

![initial_setup](https://user-images.githubusercontent.com/17613683/59344213-865b6400-8d05-11e9-9d86-381d59671530.png)


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


Details on accounts and groups
---

This interface will create POSIX user accounts and groups, which allows you to use your LDAP directory for Linux/Unix accounts.   
Groups are also created as a `groupOfUniqueNames` type in case you want to use the `memberOf` LDAP module.
