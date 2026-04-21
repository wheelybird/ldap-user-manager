<?php

include_once "i18n.inc.php";

/**
 * Configuration registry
 *
 * Centralised configuration metadata for auto-generating:
 * - System Configuration page
 * - Documentation
 * - Validation
 *
 * Each configuration entry includes:
 * - category: Which category it belongs to
 * - description: Human-readable description
 * - type: Data type (string, boolean, integer, array, etc.)
 * - default: Default value if not set
 * - mandatory: Whether the config is required
 * - env_var: Environment variable name
 * - variable: PHP variable name/path
 * - display_code: Whether to display in <code> tags
 */

# Category definitions - groups related configurations together
$CONFIG_CATEGORIES = array(
  'ldap' => array(
    'name' => t('config_cat.ldap.name'),
    'description' => t('config_cat.ldap.desc'),
    'order' => 1,
    'icon' => 'bi-diagram-3'
  ),
  'user_defaults' => array(
    'name' => t('config_cat.user_defaults.name'),
    'description' => t('config_cat.user_defaults.desc'),
    'order' => 2,
    'icon' => 'bi-person-gear'
  ),
  'mfa' => array(
    'name' => t('config_cat.mfa.name'),
    'description' => t('config_cat.mfa.desc'),
    'order' => 3,
    'icon' => 'bi-shield-lock'
  ),
  'user_profile' => array(
    'name' => t('config_cat.user_profile.name'),
    'description' => t('config_cat.user_profile.desc'),
    'order' => 4,
    'icon' => 'bi-person-badge'
  ),
  'email' => array(
    'name' => t('config_cat.email.name'),
    'description' => t('config_cat.email.desc'),
    'order' => 5,
    'icon' => 'bi-envelope'
  ),
  'interface' => array(
    'name' => t('config_cat.interface.name'),
    'description' => t('config_cat.interface.desc'),
    'order' => 6,
    'icon' => 'bi-palette'
  ),
  'security' => array(
    'name' => t('config_cat.security.name'),
    'description' => t('config_cat.security.desc'),
    'order' => 7,
    'icon' => 'bi-shield-check'
  ),
  'audit' => array(
    'name' => t('config_cat.audit.name'),
    'description' => t('config_cat.audit.desc'),
    'order' => 8,
    'icon' => 'bi-journal-text',
    'optional' => true
  ),
  'password_policy' => array(
    'name' => t('config_cat.password_policy.name'),
    'description' => t('config_cat.password_policy.desc'),
    'order' => 9,
    'icon' => 'bi-key',
    'optional' => true
  ),
  'lifecycle' => array(
    'name' => t('config_cat.lifecycle.name'),
    'description' => t('config_cat.lifecycle.desc'),
    'order' => 10,
    'icon' => 'bi-arrow-repeat',
    'optional' => true
  ),
  'group_mgmt' => array(
    'name' => t('config_cat.group_mgmt.name'),
    'description' => t('config_cat.group_mgmt.desc'),
    'order' => 11,
    'icon' => 'bi-people',
    'optional' => true
  ),
  'debug' => array(
    'name' => t('config_cat.debug.name'),
    'description' => t('config_cat.debug.desc'),
    'order' => 99,
    'icon' => 'bi-bug'
  )
);

# Configuration Registry
# All configurable settings with their metadata
$CONFIG_REGISTRY = array(

  // ===== LDAP Directory Settings =====

  'LDAP_URI' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_URI.desc'),
    'help' => t('config.LDAP_URI.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => true,
    'env_var' => 'LDAP_URI',
    'variable' => '$LDAP[\'uri\']',
    'display_code' => true
  ),

  'LDAP_BASE_DN' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_BASE_DN.desc'),
    'help' => t('config.LDAP_BASE_DN.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => true,
    'env_var' => 'LDAP_BASE_DN',
    'variable' => '$LDAP[\'base_dn\']',
    'display_code' => true
  ),

  'LDAP_ADMIN_BIND_DN' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_ADMIN_BIND_DN.desc'),
    'help' => t('config.LDAP_ADMIN_BIND_DN.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => true,
    'env_var' => 'LDAP_ADMIN_BIND_DN',
    'variable' => '$LDAP[\'admin_bind_dn\']',
    'display_code' => true,
    'sensitive' => true
  ),

  'LDAP_ADMIN_BIND_PWD' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_ADMIN_BIND_PWD.desc'),
    'help' => t('config.LDAP_ADMIN_BIND_PWD.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => true,
    'env_var' => 'LDAP_ADMIN_BIND_PWD',
    'variable' => '$LDAP[\'admin_bind_pwd\']',
    'sensitive' => true,
    'hide_value' => true
  ),

  'LDAP_USER_OU' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_USER_OU.desc'),
    'help' => t('config.LDAP_USER_OU.help'),
    'type' => 'string',
    'default' => 'people',
    'mandatory' => false,
    'env_var' => 'LDAP_USER_OU',
    'variable' => '$LDAP[\'user_ou\']'
  ),

  'LDAP_GROUP_OU' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_GROUP_OU.desc'),
    'help' => t('config.LDAP_GROUP_OU.help'),
    'type' => 'string',
    'default' => 'groups',
    'mandatory' => false,
    'env_var' => 'LDAP_GROUP_OU',
    'variable' => '$LDAP[\'group_ou\']'
  ),

  'LDAP_ADMINS_GROUP' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_ADMINS_GROUP.desc'),
    'help' => t('config.LDAP_ADMINS_GROUP.help'),
    'type' => 'string',
    'default' => 'admins',
    'mandatory' => false,
    'env_var' => 'LDAP_ADMINS_GROUP',
    'variable' => '$LDAP[\'admins_group\']'
  ),

  'LDAP_ACCOUNT_ATTRIBUTE' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_ACCOUNT_ATTRIBUTE.desc'),
    'help' => t('config.LDAP_ACCOUNT_ATTRIBUTE.help'),
    'type' => 'string',
    'default' => 'uid',
    'mandatory' => false,
    'env_var' => 'LDAP_ACCOUNT_ATTRIBUTE',
    'variable' => '$LDAP[\'account_attribute\']',
    'display_code' => true
  ),

  'LDAP_GROUP_ATTRIBUTE' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_GROUP_ATTRIBUTE.desc'),
    'help' => t('config.LDAP_GROUP_ATTRIBUTE.help'),
    'type' => 'string',
    'default' => 'cn',
    'mandatory' => false,
    'env_var' => 'LDAP_GROUP_ATTRIBUTE',
    'variable' => '$LDAP[\'group_attribute\']',
    'display_code' => true
  ),

  'LDAP_REQUIRE_STARTTLS' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_REQUIRE_STARTTLS.desc'),
    'help' => t('config.LDAP_REQUIRE_STARTTLS.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'LDAP_REQUIRE_STARTTLS',
    'variable' => '$LDAP[\'require_starttls\']'
  ),

  'LDAP_IGNORE_CERT_ERRORS' => array(
    'category' => 'ldap',
    'description' => t('config.LDAP_IGNORE_CERT_ERRORS.desc'),
    'help' => t('config.LDAP_IGNORE_CERT_ERRORS.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'LDAP_IGNORE_CERT_ERRORS',
    'variable' => '$LDAP[\'ignore_cert_errors\']'
  ),

  'FORCE_RFC2307BIS' => array(
    'category' => 'ldap',
    'description' => t('config.FORCE_RFC2307BIS.desc'),
    'help' => t('config.FORCE_RFC2307BIS.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'FORCE_RFC2307BIS',
    'variable' => '$LDAP[\'forced_rfc2307bis\']'
  ),

  // ===== User Account Defaults =====

  'DEFAULT_USER_GROUP' => array(
    'category' => 'user_defaults',
    'description' => t('config.DEFAULT_USER_GROUP.desc'),
    'help' => t('config.DEFAULT_USER_GROUP.help'),
    'type' => 'string',
    'default' => 'everybody',
    'mandatory' => false,
    'env_var' => 'DEFAULT_USER_GROUP',
    'variable' => '$DEFAULT_USER_GROUP'
  ),

  'DEFAULT_USER_SHELL' => array(
    'category' => 'user_defaults',
    'description' => t('config.DEFAULT_USER_SHELL.desc'),
    'help' => t('config.DEFAULT_USER_SHELL.help'),
    'type' => 'string',
    'default' => '/bin/bash',
    'mandatory' => false,
    'env_var' => 'DEFAULT_USER_SHELL',
    'variable' => '$DEFAULT_USER_SHELL',
    'display_code' => true
  ),

  'USERNAME_FORMAT' => array(
    'category' => 'user_defaults',
    'description' => t('config.USERNAME_FORMAT.desc'),
    'help' => t('config.USERNAME_FORMAT.help'),
    'type' => 'string',
    'default' => '{first_name}-{last_name}',
    'mandatory' => false,
    'env_var' => 'USERNAME_FORMAT',
    'variable' => '$USERNAME_FORMAT',
    'display_code' => true
  ),

  'USERNAME_REGEX' => array(
    'category' => 'user_defaults',
    'description' => t('config.USERNAME_REGEX.desc'),
    'help' => t('config.USERNAME_REGEX.help'),
    'type' => 'string',
    'default' => '^[\p{L}\p{N}_.-]{2,64}$',
    'mandatory' => false,
    'env_var' => 'USERNAME_REGEX',
    'variable' => '$USERNAME_REGEX',
    'display_code' => true
  ),

  'ENFORCE_USERNAME_VALIDATION' => array(
    'category' => 'user_defaults',
    'description' => t('config.ENFORCE_USERNAME_VALIDATION.desc'),
    'help' => t('config.ENFORCE_USERNAME_VALIDATION.help'),
    'type' => 'boolean',
    'default' => true,
    'mandatory' => false,
    'env_var' => 'ENFORCE_USERNAME_VALIDATION',
    'variable' => '$ENFORCE_USERNAME_VALIDATION'
  ),

  'ACCEPT_WEAK_PASSWORDS' => array(
    'category' => 'user_defaults',
    'description' => t('config.ACCEPT_WEAK_PASSWORDS.desc'),
    'help' => t('config.ACCEPT_WEAK_PASSWORDS.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'ACCEPT_WEAK_PASSWORDS',
    'variable' => '$ACCEPT_WEAK_PASSWORDS'
  ),

  'SHOW_POSIX_ATTRIBUTES' => array(
    'category' => 'user_defaults',
    'description' => t('config.SHOW_POSIX_ATTRIBUTES.desc'),
    'help' => t('config.SHOW_POSIX_ATTRIBUTES.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'SHOW_POSIX_ATTRIBUTES',
    'variable' => '$SHOW_POSIX_ATTRIBUTES'
  ),

  'PASSWORD_HASH' => array(
    'category' => 'user_defaults',
    'description' => t('config.PASSWORD_HASH.desc'),
    'help' => t('config.PASSWORD_HASH.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => false,
    'env_var' => 'PASSWORD_HASH',
    'variable' => '$PASSWORD_HASH'
  ),

  'EMAIL_DOMAIN' => array(
    'category' => 'user_defaults',
    'description' => t('config.EMAIL_DOMAIN.desc'),
    'help' => t('config.EMAIL_DOMAIN.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => false,
    'env_var' => 'EMAIL_DOMAIN',
    'variable' => '$EMAIL_DOMAIN',
    'display_code' => true
  ),

  // ===== Multi-Factor Authentication =====

  'MFA_FEATURE_ENABLED' => array(
    'category' => 'mfa',
    'description' => t('config.MFA_FEATURE_ENABLED.desc'),
    'help' => t('config.MFA_FEATURE_ENABLED.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'MFA_FEATURE_ENABLED',
    'variable' => '$MFA_FEATURE_ENABLED'
  ),

  'MFA_REQUIRED_GROUPS' => array(
    'category' => 'mfa',
    'description' => t('config.MFA_REQUIRED_GROUPS.desc'),
    'help' => t('config.MFA_REQUIRED_GROUPS.help'),
    'type' => 'array',
    'default' => array(),
    'mandatory' => false,
    'env_var' => 'MFA_REQUIRED_GROUPS',
    'variable' => '$MFA_REQUIRED_GROUPS'
  ),

  'MFA_GRACE_PERIOD_DAYS' => array(
    'category' => 'mfa',
    'description' => t('config.MFA_GRACE_PERIOD_DAYS.desc'),
    'help' => t('config.MFA_GRACE_PERIOD_DAYS.help'),
    'type' => 'integer',
    'default' => 7,
    'mandatory' => false,
    'env_var' => 'MFA_GRACE_PERIOD_DAYS',
    'variable' => '$MFA_GRACE_PERIOD_DAYS'
  ),

  'MFA_TOTP_ISSUER' => array(
    'category' => 'mfa',
    'description' => t('config.MFA_TOTP_ISSUER.desc'),
    'help' => t('config.MFA_TOTP_ISSUER.help'),
    'type' => 'string',
    'default' => 'Luminary',
    'mandatory' => false,
    'env_var' => 'MFA_TOTP_ISSUER',
    'variable' => '$MFA_TOTP_ISSUER'
  ),

  'TOTP_SECRET_ATTRIBUTE' => array(
    'category' => 'mfa',
    'description' => t('config.TOTP_SECRET_ATTRIBUTE.desc'),
    'help' => t('config.TOTP_SECRET_ATTRIBUTE.help'),
    'type' => 'string',
    'default' => 'totpSecret',
    'mandatory' => false,
    'env_var' => 'TOTP_SECRET_ATTRIBUTE',
    'variable' => '$TOTP_ATTRS[\'secret\']',
    'display_code' => true
  ),

  'TOTP_STATUS_ATTRIBUTE' => array(
    'category' => 'mfa',
    'description' => t('config.TOTP_STATUS_ATTRIBUTE.desc'),
    'help' => t('config.TOTP_STATUS_ATTRIBUTE.help'),
    'type' => 'string',
    'default' => 'totpStatus',
    'mandatory' => false,
    'env_var' => 'TOTP_STATUS_ATTRIBUTE',
    'variable' => '$TOTP_ATTRS[\'status\']',
    'display_code' => true
  ),

  'TOTP_ENROLLED_DATE_ATTRIBUTE' => array(
    'category' => 'mfa',
    'description' => t('config.TOTP_ENROLLED_DATE_ATTRIBUTE.desc'),
    'help' => t('config.TOTP_ENROLLED_DATE_ATTRIBUTE.help'),
    'type' => 'string',
    'default' => 'totpEnrolledDate',
    'mandatory' => false,
    'env_var' => 'TOTP_ENROLLED_DATE_ATTRIBUTE',
    'variable' => '$TOTP_ATTRS[\'enrolled_date\']',
    'display_code' => true
  ),

  'TOTP_SCRATCH_CODES_ATTRIBUTE' => array(
    'category' => 'mfa',
    'description' => t('config.TOTP_SCRATCH_CODES_ATTRIBUTE.desc'),
    'help' => t('config.TOTP_SCRATCH_CODES_ATTRIBUTE.help'),
    'type' => 'string',
    'default' => 'totpScratchCode',
    'mandatory' => false,
    'env_var' => 'TOTP_SCRATCH_CODES_ATTRIBUTE',
    'variable' => '$TOTP_ATTRS[\'scratch_codes\']',
    'display_code' => true
  ),

  'TOTP_OBJECTCLASS' => array(
    'category' => 'mfa',
    'description' => t('config.TOTP_OBJECTCLASS.desc'),
    'help' => t('config.TOTP_OBJECTCLASS.help'),
    'type' => 'string',
    'default' => 'totpUser',
    'mandatory' => false,
    'env_var' => 'TOTP_OBJECTCLASS',
    'variable' => '$TOTP_ATTRS[\'objectclass\']',
    'display_code' => true
  ),

  'GROUP_MFA_OBJECTCLASS' => array(
    'category' => 'mfa',
    'description' => t('config.GROUP_MFA_OBJECTCLASS.desc'),
    'help' => t('config.GROUP_MFA_OBJECTCLASS.help'),
    'type' => 'string',
    'default' => 'mfaGroup',
    'mandatory' => false,
    'env_var' => 'GROUP_MFA_OBJECTCLASS',
    'variable' => '$GROUP_MFA_ATTRS[\'objectclass\']',
    'display_code' => true
  ),

  'GROUP_MFA_REQUIRED_ATTRIBUTE' => array(
    'category' => 'mfa',
    'description' => t('config.GROUP_MFA_REQUIRED_ATTRIBUTE.desc'),
    'help' => t('config.GROUP_MFA_REQUIRED_ATTRIBUTE.help'),
    'type' => 'string',
    'default' => 'mfaRequired',
    'mandatory' => false,
    'env_var' => 'GROUP_MFA_REQUIRED_ATTRIBUTE',
    'variable' => '$GROUP_MFA_ATTRS[\'required\']',
    'display_code' => true
  ),

  'GROUP_MFA_GRACE_PERIOD_ATTRIBUTE' => array(
    'category' => 'mfa',
    'description' => t('config.GROUP_MFA_GRACE_PERIOD_ATTRIBUTE.desc'),
    'help' => t('config.GROUP_MFA_GRACE_PERIOD_ATTRIBUTE.help'),
    'type' => 'string',
    'default' => 'mfaGracePeriodDays',
    'mandatory' => false,
    'env_var' => 'GROUP_MFA_GRACE_PERIOD_ATTRIBUTE',
    'variable' => '$GROUP_MFA_ATTRS[\'grace_period\']',
    'display_code' => true
  ),

  // ===== Password Reset Settings =====

  'PASSWORD_RESET_ENABLED' => array(
    'category' => 'password_reset',
    'description' => t('config.PASSWORD_RESET_ENABLED.desc'),
    'help' => t('config.PASSWORD_RESET_ENABLED.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'PASSWORD_RESET_ENABLED',
    'variable' => '$PASSWORD_RESET_ENABLED'
  ),

  'USE_LDAP_AS_DB' => array(
    'category' => 'password_reset',
    'description' => t('config.USE_LDAP_AS_DB.desc'),
    'help' => t('config.USE_LDAP_AS_DB.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'USE_LDAP_AS_DB',
    'variable' => '$USE_LDAP_AS_DB'
  ),

  'PASSWORD_RESET_TOKEN_EXPIRY_MINUTES' => array(
    'category' => 'password_reset',
    'description' => t('config.PASSWORD_RESET_TOKEN_EXPIRY_MINUTES.desc'),
    'help' => t('config.PASSWORD_RESET_TOKEN_EXPIRY_MINUTES.help'),
    'type' => 'integer',
    'default' => 60,
    'mandatory' => false,
    'env_var' => 'PASSWORD_RESET_TOKEN_EXPIRY_MINUTES',
    'variable' => '$PASSWORD_RESET_TOKEN_EXPIRY_MINUTES'
  ),

  'PASSWORD_RESET_RATE_LIMIT_REQUESTS' => array(
    'category' => 'password_reset',
    'description' => t('config.PASSWORD_RESET_RATE_LIMIT_REQUESTS.desc'),
    'help' => t('config.PASSWORD_RESET_RATE_LIMIT_REQUESTS.help'),
    'type' => 'integer',
    'default' => 3,
    'mandatory' => false,
    'env_var' => 'PASSWORD_RESET_RATE_LIMIT_REQUESTS',
    'variable' => '$PASSWORD_RESET_RATE_LIMIT_REQUESTS'
  ),

  'PASSWORD_RESET_RATE_LIMIT_WINDOW_MINUTES' => array(
    'category' => 'password_reset',
    'description' => t('config.PASSWORD_RESET_RATE_LIMIT_WINDOW_MINUTES.desc'),
    'help' => t('config.PASSWORD_RESET_RATE_LIMIT_WINDOW_MINUTES.help'),
    'type' => 'integer',
    'default' => 60,
    'mandatory' => false,
    'env_var' => 'PASSWORD_RESET_RATE_LIMIT_WINDOW_MINUTES',
    'variable' => '$PASSWORD_RESET_RATE_LIMIT_WINDOW_MINUTES'
  ),

  'PASSWORD_RESET_MAX_ATTEMPTS' => array(
    'category' => 'password_reset',
    'description' => t('config.PASSWORD_RESET_MAX_ATTEMPTS.desc'),
    'help' => t('config.PASSWORD_RESET_MAX_ATTEMPTS.help'),
    'type' => 'integer',
    'default' => 5,
    'mandatory' => false,
    'env_var' => 'PASSWORD_RESET_MAX_ATTEMPTS',
    'variable' => '$PASSWORD_RESET_MAX_ATTEMPTS'
  ),

  'PASSWORD_RESET_LOCKOUT_DURATION_MINUTES' => array(
    'category' => 'password_reset',
    'description' => t('config.PASSWORD_RESET_LOCKOUT_DURATION_MINUTES.desc'),
    'help' => t('config.PASSWORD_RESET_LOCKOUT_DURATION_MINUTES.help'),
    'type' => 'integer',
    'default' => 60,
    'mandatory' => false,
    'env_var' => 'PASSWORD_RESET_LOCKOUT_DURATION_MINUTES',
    'variable' => '$PASSWORD_RESET_LOCKOUT_DURATION_MINUTES'
  ),

  // ===== User Profile Settings =====

  'DEFAULT_USER_EDITABLE_ATTRIBUTES' => array(
    'category' => 'user_profile',
    'description' => t('config.DEFAULT_USER_EDITABLE_ATTRIBUTES.desc'),
    'help' => t('config.DEFAULT_USER_EDITABLE_ATTRIBUTES.help'),
    'type' => 'array',
    'default' => array(
      'telephonenumber',
      'mobile',
      'displayname',
      'description',
      'title',
      'jpegphoto',
      'sshpublickey'
    ),
    'mandatory' => false,
    'env_var' => null,
    'variable' => '$DEFAULT_USER_EDITABLE_ATTRIBUTES'
  ),

  'USER_EDITABLE_ATTRIBUTES' => array(
    'category' => 'user_profile',
    'description' => t('config.USER_EDITABLE_ATTRIBUTES.desc'),
    'help' => t('config.USER_EDITABLE_ATTRIBUTES.help'),
    'type' => 'array',
    'default' => array(),
    'mandatory' => false,
    'env_var' => 'USER_EDITABLE_ATTRIBUTES',
    'variable' => '$ADMIN_USER_EDITABLE_ATTRIBUTES'
  ),

  'ATTRIBUTE_BLACKLIST' => array(
    'category' => 'user_profile',
    'description' => t('config.ATTRIBUTE_BLACKLIST.desc'),
    'help' => t('config.ATTRIBUTE_BLACKLIST.help'),
    'type' => 'array',
    'default' => array(
      'dn', 'uid', 'cn', 'objectclass',
      'uidnumber', 'gidnumber', 'homedirectory', 'loginshell',
      'userpassword', 'sambantpassword', 'sambapassword',
      'memberof', 'member', 'memberuid', 'uniquemember',
      'totpsecret', 'totpstatus', 'totpenrolleddate', 'totpscratchcode',
      'creatorsname', 'createtimestamp', 'modifiersname', 'modifytimestamp',
      'entrydn', 'entryuuid', 'structuralobjectclass', 'hassubordinates', 'subschemasubentry'
    ),
    'mandatory' => false,
    'env_var' => null,
    'variable' => '$ATTRIBUTE_BLACKLIST'
  ),

  // ===== Email Settings =====

  'SMTP_HOSTNAME' => array(
    'category' => 'email',
    'description' => t('config.SMTP_HOSTNAME.desc'),
    'help' => t('config.SMTP_HOSTNAME.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => false,
    'env_var' => 'SMTP_HOSTNAME',
    'variable' => '$SMTP[\'host\']',
    'display_code' => true
  ),

  'SMTP_HOST_PORT' => array(
    'category' => 'email',
    'description' => t('config.SMTP_HOST_PORT.desc'),
    'help' => t('config.SMTP_HOST_PORT.help'),
    'type' => 'integer',
    'default' => 25,
    'mandatory' => false,
    'env_var' => 'SMTP_HOST_PORT',
    'variable' => '$SMTP[\'port\']'
  ),

  'SMTP_USERNAME' => array(
    'category' => 'email',
    'description' => t('config.SMTP_USERNAME.desc'),
    'help' => t('config.SMTP_USERNAME.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => false,
    'env_var' => 'SMTP_USERNAME',
    'variable' => '$SMTP[\'user\']',
    'sensitive' => true
  ),

  'SMTP_PASSWORD' => array(
    'category' => 'email',
    'description' => t('config.SMTP_PASSWORD.desc'),
    'help' => t('config.SMTP_PASSWORD.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => false,
    'env_var' => 'SMTP_PASSWORD',
    'variable' => '$SMTP[\'pass\']',
    'sensitive' => true,
    'hide_value' => true
  ),

  'SMTP_USE_TLS' => array(
    'category' => 'email',
    'description' => t('config.SMTP_USE_TLS.desc'),
    'help' => t('config.SMTP_USE_TLS.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'SMTP_USE_TLS',
    'variable' => '$SMTP[\'tls\']'
  ),

  'SMTP_USE_SSL' => array(
    'category' => 'email',
    'description' => t('config.SMTP_USE_SSL.desc'),
    'help' => t('config.SMTP_USE_SSL.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'SMTP_USE_SSL',
    'variable' => '$SMTP[\'ssl\']'
  ),

  'SMTP_HELO_HOST' => array(
    'category' => 'email',
    'description' => t('config.SMTP_HELO_HOST.desc'),
    'help' => t('config.SMTP_HELO_HOST.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => false,
    'env_var' => 'SMTP_HELO_HOST',
    'variable' => '$SMTP[\'helo\']'
  ),

  'EMAIL_FROM_ADDRESS' => array(
    'category' => 'email',
    'description' => t('config.EMAIL_FROM_ADDRESS.desc'),
    'help' => t('config.EMAIL_FROM_ADDRESS.help'),
    'type' => 'string',
    'default' => 'admin@luminary.id',
    'mandatory' => false,
    'env_var' => 'EMAIL_FROM_ADDRESS',
    'variable' => '$EMAIL[\'from_address\']',
    'display_code' => true
  ),

  'EMAIL_FROM_NAME' => array(
    'category' => 'email',
    'description' => t('config.EMAIL_FROM_NAME.desc'),
    'help' => t('config.EMAIL_FROM_NAME.help'),
    'type' => 'string',
    'default' => 'Luminary',
    'mandatory' => false,
    'env_var' => 'EMAIL_FROM_NAME',
    'variable' => '$EMAIL[\'from_name\']'
  ),

  'EMAIL_REPLY_TO_ADDRESS' => array(
    'category' => 'email',
    'description' => t('config.EMAIL_REPLY_TO_ADDRESS.desc'),
    'help' => t('config.EMAIL_REPLY_TO_ADDRESS.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => false,
    'env_var' => 'EMAIL_REPLY_TO_ADDRESS',
    'variable' => '$EMAIL[\'reply_to_address\']',
    'display_code' => true
  ),

  'EMAIL_USER_ON_PASSWORD_CHANGE' => array(
    'category' => 'email',
    'description' => t('config.EMAIL_USER_ON_PASSWORD_CHANGE.desc'),
    'help' => t('config.EMAIL_USER_ON_PASSWORD_CHANGE.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'EMAIL_USER_ON_PASSWORD_CHANGE',
    'variable' => '$EMAIL_USER_ON_PASSWORD_CHANGE'
  ),

  'EMAIL_ADMIN_ON_USER_PASSWORD_CHANGE' => array(
    'category' => 'email',
    'description' => t('config.EMAIL_ADMIN_ON_USER_PASSWORD_CHANGE.desc'),
    'help' => t('config.EMAIL_ADMIN_ON_USER_PASSWORD_CHANGE.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'EMAIL_ADMIN_ON_USER_PASSWORD_CHANGE',
    'variable' => '$EMAIL_ADMIN_ON_USER_PASSWORD_CHANGE'
  ),

  'ADMIN_EMAIL' => array(
    'category' => 'email',
    'description' => t('config.ADMIN_EMAIL.desc'),
    'help' => t('config.ADMIN_EMAIL.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => false,
    'env_var' => 'ADMIN_EMAIL',
    'variable' => '$ADMIN_EMAIL',
    'display_code' => true
  ),

  'ACCOUNT_REQUESTS_ENABLED' => array(
    'category' => 'email',
    'description' => t('config.ACCOUNT_REQUESTS_ENABLED.desc'),
    'help' => t('config.ACCOUNT_REQUESTS_ENABLED.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'ACCOUNT_REQUESTS_ENABLED',
    'variable' => '$ACCOUNT_REQUESTS_ENABLED'
  ),

  'ACCOUNT_REQUESTS_EMAIL' => array(
    'category' => 'email',
    'description' => t('config.ACCOUNT_REQUESTS_EMAIL.desc'),
    'help' => t('config.ACCOUNT_REQUESTS_EMAIL.help'),
    'type' => 'string',
    'default' => null,
    'mandatory' => false,
    'env_var' => 'ACCOUNT_REQUESTS_EMAIL',
    'variable' => '$ACCOUNT_REQUESTS_EMAIL',
    'display_code' => true
  ),

  // ===== Interface & Branding =====

  'ORGANISATION_NAME' => array(
    'category' => 'interface',
    'description' => t('config.ORGANISATION_NAME.desc'),
    'help' => t('config.ORGANISATION_NAME.help'),
    'type' => 'string',
    'default' => 'Luminary',
    'mandatory' => false,
    'env_var' => 'ORGANISATION_NAME',
    'variable' => '$ORGANISATION_NAME'
  ),

  'SITE_NAME' => array(
    'category' => 'interface',
    'description' => t('config.SITE_NAME.desc'),
    'help' => t('config.SITE_NAME.help'),
    'type' => 'string',
    'default' => 'Luminary',
    'mandatory' => false,
    'env_var' => 'SITE_NAME',
    'variable' => '$SITE_NAME'
  ),

  'SERVER_HOSTNAME' => array(
    'category' => 'interface',
    'description' => t('config.SERVER_HOSTNAME.desc'),
    'help' => t('config.SERVER_HOSTNAME.help'),
    'type' => 'string',
    'default' => 'luminary.id',
    'mandatory' => false,
    'env_var' => 'SERVER_HOSTNAME',
    'variable' => '$SERVER_HOSTNAME'
  ),

  'SERVER_PATH' => array(
    'category' => 'interface',
    'description' => t('config.SERVER_PATH.desc'),
    'help' => t('config.SERVER_PATH.help'),
    'type' => 'string',
    'default' => '/',
    'mandatory' => false,
    'env_var' => 'SERVER_PATH',
    'variable' => '$SERVER_PATH'
  ),

  'SITE_LOGIN_FIELD_LABEL' => array(
    'category' => 'interface',
    'description' => t('config.SITE_LOGIN_FIELD_LABEL.desc'),
    'help' => t('config.SITE_LOGIN_FIELD_LABEL.help'),
    'type' => 'string',
    'default' =>  t('login.username'),
    'mandatory' => false,
    'env_var' => 'SITE_LOGIN_FIELD_LABEL',
    'variable' => '$SITE_LOGIN_FIELD_LABEL'
  ),

  'SITE_LOGIN_LDAP_ATTRIBUTE' => array(
    'category' => 'interface',
    'description' => t('config.SITE_LOGIN_LDAP_ATTRIBUTE.desc'),
    'help' => t('config.SITE_LOGIN_LDAP_ATTRIBUTE.help'),
    'type' => 'string',
    'default' => 'uid',
    'mandatory' => false,
    'env_var' => 'SITE_LOGIN_LDAP_ATTRIBUTE',
    'variable' => '$SITE_LOGIN_LDAP_ATTRIBUTE',
    'display_code' => true
  ),

  'CUSTOM_LOGO' => array(
    'category' => 'interface',
    'description' => t('config.CUSTOM_LOGO.desc'),
    'help' => t('config.CUSTOM_LOGO.help'),
    'type' => 'string',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'CUSTOM_LOGO',
    'variable' => '$CUSTOM_LOGO'
  ),

  'CUSTOM_STYLES' => array(
    'category' => 'interface',
    'description' => t('config.CUSTOM_STYLES.desc'),
    'help' => t('config.CUSTOM_STYLES.help'),
    'type' => 'string',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'CUSTOM_STYLES',
    'variable' => '$CUSTOM_STYLES'
  ),

  'PAGINATION_ITEMS_PER_PAGE' => array(
    'category' => 'interface',
    'description' => t('config.PAGINATION_ITEMS_PER_PAGE.desc'),
    'help' => t('config.PAGINATION_ITEMS_PER_PAGE.help'),
    'type' => 'integer',
    'default' => 50,
    'mandatory' => false,
    'env_var' => 'PAGINATION_ITEMS_PER_PAGE',
    'variable' => '$PAGINATION_ITEMS_PER_PAGE'
  ),

  // ===== Session & Security =====

  'SESSION_TIMEOUT' => array(
    'category' => 'security',
    'description' => t('config.SESSION_TIMEOUT.desc'),
    'help' => t('config.SESSION_TIMEOUT.help'),
    'type' => 'integer',
    'default' => 10,
    'mandatory' => false,
    'env_var' => 'SESSION_TIMEOUT',
    'variable' => '$SESSION_TIMEOUT'
  ),

  'NO_HTTPS' => array(
    'category' => 'security',
    'description' => t('config.NO_HTTPS.desc'),
    'help' => t('config.NO_HTTPS.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'NO_HTTPS',
    'variable' => '$NO_HTTPS'
  ),

  'REMOTE_HTTP_HEADERS_LOGIN' => array(
    'category' => 'security',
    'description' => t('config.REMOTE_HTTP_HEADERS_LOGIN.desc'),
    'help' => t('config.REMOTE_HTTP_HEADERS_LOGIN.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'REMOTE_HTTP_HEADERS_LOGIN',
    'variable' => '$REMOTE_HTTP_HEADERS_LOGIN'
  ),

  // ===== Audit Logging (Optional Feature) =====

  'AUDIT_ENABLED' => array(
    'category' => 'audit',
    'description' => t('config.AUDIT_ENABLED.desc'),
    'help' => t('config.AUDIT_ENABLED.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'AUDIT_ENABLED',
    'variable' => '$AUDIT_ENABLED'
  ),

  'AUDIT_LOG_FILE' => array(
    'category' => 'audit',
    'description' => t('config.AUDIT_LOG_FILE.desc'),
    'help' => t('config.AUDIT_LOG_FILE.help'),
    'type' => 'string',
    'default' => 'stdout',
    'mandatory' => false,
    'env_var' => 'AUDIT_LOG_FILE',
    'variable' => '$AUDIT_LOG_FILE',
    'display_code' => true
  ),

  'AUDIT_LOG_RETENTION_DAYS' => array(
    'category' => 'audit',
    'description' => t('config.AUDIT_LOG_RETENTION_DAYS.desc'),
    'help' => t('config.AUDIT_LOG_RETENTION_DAYS.help'),
    'type' => 'integer',
    'default' => 90,
    'mandatory' => false,
    'env_var' => 'AUDIT_LOG_RETENTION_DAYS',
    'variable' => '$AUDIT_LOG_RETENTION_DAYS'
  ),

  // ===== Password Policy (Optional Feature) =====

  'PASSWORD_POLICY_ENABLED' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_POLICY_ENABLED.desc'),
    'help' => t('config.PASSWORD_POLICY_ENABLED.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'PASSWORD_POLICY_ENABLED',
    'variable' => '$PASSWORD_POLICY_ENABLED'
  ),

  'PPOLICY_ENABLED' => array(
    'category' => 'password_policy',
    'description' => t('config.PPOLICY_ENABLED.desc'),
    'help' => t('config.PPOLICY_ENABLED.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'PPOLICY_ENABLED',
    'variable' => '$PPOLICY_ENABLED'
  ),

  'PASSWORD_MIN_LENGTH' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_MIN_LENGTH.desc'),
    'help' => t('config.PASSWORD_MIN_LENGTH.help'),
    'type' => 'integer',
    'default' => 8,
    'mandatory' => false,
    'env_var' => 'PASSWORD_MIN_LENGTH',
    'variable' => '$PASSWORD_MIN_LENGTH'
  ),

  'PASSWORD_REQUIRE_UPPERCASE' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_REQUIRE_UPPERCASE.desc'),
    'help' => t('config.PASSWORD_REQUIRE_UPPERCASE.help'),
    'type' => 'boolean',
    'default' => true,
    'mandatory' => false,
    'env_var' => 'PASSWORD_REQUIRE_UPPERCASE',
    'variable' => '$PASSWORD_REQUIRE_UPPERCASE'
  ),

  'PASSWORD_REQUIRE_LOWERCASE' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_REQUIRE_LOWERCASE.desc'),
    'help' => t('config.PASSWORD_REQUIRE_LOWERCASE.help'),
    'type' => 'boolean',
    'default' => true,
    'mandatory' => false,
    'env_var' => 'PASSWORD_REQUIRE_LOWERCASE',
    'variable' => '$PASSWORD_REQUIRE_LOWERCASE'
  ),

  'PASSWORD_REQUIRE_NUMBERS' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_REQUIRE_NUMBERS.desc'),
    'help' => t('config.PASSWORD_REQUIRE_NUMBERS.help'),
    'type' => 'boolean',
    'default' => true,
    'mandatory' => false,
    'env_var' => 'PASSWORD_REQUIRE_NUMBERS',
    'variable' => '$PASSWORD_REQUIRE_NUMBERS'
  ),

  'PASSWORD_REQUIRE_SPECIAL' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_REQUIRE_SPECIAL.desc'),
    'help' => t('config.PASSWORD_REQUIRE_SPECIAL.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'PASSWORD_REQUIRE_SPECIAL',
    'variable' => '$PASSWORD_REQUIRE_SPECIAL'
  ),

  'PASSWORD_MIN_SCORE' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_MIN_SCORE.desc'),
    'help' => t('config.PASSWORD_MIN_SCORE.help'),
    'type' => 'integer',
    'default' => 3,
    'mandatory' => false,
    'env_var' => 'PASSWORD_MIN_SCORE',
    'variable' => '$PASSWORD_MIN_SCORE'
  ),

  'PASSWORD_HISTORY_COUNT' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_HISTORY_COUNT.desc'),
    'help' => t('config.PASSWORD_HISTORY_COUNT.help'),
    'type' => 'integer',
    'default' => 0,
    'mandatory' => false,
    'env_var' => 'PASSWORD_HISTORY_COUNT',
    'variable' => '$PASSWORD_HISTORY_COUNT'
  ),

  'PASSWORD_EXPIRY_DAYS' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_EXPIRY_DAYS.desc'),
    'help' => t('config.PASSWORD_EXPIRY_DAYS.help'),
    'type' => 'integer',
    'default' => 0,
    'mandatory' => false,
    'env_var' => 'PASSWORD_EXPIRY_DAYS',
    'variable' => '$PASSWORD_EXPIRY_DAYS'
  ),

  'PASSWORD_EXPIRY_WARNING_DAYS' => array(
    'category' => 'password_policy',
    'description' => t('config.PASSWORD_EXPIRY_WARNING_DAYS.desc'),
    'help' => t('config.PASSWORD_EXPIRY_WARNING_DAYS.help'),
    'type' => 'integer',
    'default' => 7,
    'mandatory' => false,
    'env_var' => 'PASSWORD_EXPIRY_WARNING_DAYS',
    'variable' => '$PASSWORD_EXPIRY_WARNING_DAYS'
  ),

  // ===== Account Lifecycle (Optional Feature) =====

  'LIFECYCLE_ENABLED' => array(
    'category' => 'lifecycle',
    'description' => t('config.LIFECYCLE_ENABLED.desc'),
    'help' => t('config.LIFECYCLE_ENABLED.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'LIFECYCLE_ENABLED',
    'variable' => '$LIFECYCLE_ENABLED'
  ),

  'ACCOUNT_EXPIRY_ENABLED' => array(
    'category' => 'lifecycle',
    'description' => t('config.ACCOUNT_EXPIRY_ENABLED.desc'),
    'help' => t('config.ACCOUNT_EXPIRY_ENABLED.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'ACCOUNT_EXPIRY_ENABLED',
    'variable' => '$ACCOUNT_EXPIRY_ENABLED'
  ),

  'ACCOUNT_INACTIVE_DAYS' => array(
    'category' => 'lifecycle',
    'description' => t('config.ACCOUNT_INACTIVE_DAYS.desc'),
    'help' => t('config.ACCOUNT_INACTIVE_DAYS.help'),
    'type' => 'integer',
    'default' => 90,
    'mandatory' => false,
    'env_var' => 'ACCOUNT_INACTIVE_DAYS',
    'variable' => '$ACCOUNT_INACTIVE_DAYS'
  ),

  'ACCOUNT_EXPIRY_WARNING_DAYS' => array(
    'category' => 'lifecycle',
    'description' => t('config.ACCOUNT_EXPIRY_WARNING_DAYS.desc'),
    'help' => t('config.ACCOUNT_EXPIRY_WARNING_DAYS.help'),
    'type' => 'integer',
    'default' => 14,
    'mandatory' => false,
    'env_var' => 'ACCOUNT_EXPIRY_WARNING_DAYS',
    'variable' => '$ACCOUNT_EXPIRY_WARNING_DAYS'
  ),

  'ACCOUNT_CLEANUP_ENABLED' => array(
    'category' => 'lifecycle',
    'description' => t('config.ACCOUNT_CLEANUP_ENABLED.desc'),
    'help' => t('config.ACCOUNT_CLEANUP_ENABLED.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'ACCOUNT_CLEANUP_ENABLED',
    'variable' => '$ACCOUNT_CLEANUP_ENABLED'
  ),

  // ===== Debug & Logging =====

  'LDAP_DEBUG' => array(
    'category' => 'debug',
    'description' => t('config.LDAP_DEBUG.desc'),
    'help' => t('config.LDAP_DEBUG.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'LDAP_DEBUG',
    'variable' => '$LDAP_DEBUG'
  ),

  'LDAP_VERBOSE_CONNECTION_LOGS' => array(
    'category' => 'debug',
    'description' => t('config.LDAP_VERBOSE_CONNECTION_LOGS.desc'),
    'help' => t('config.LDAP_VERBOSE_CONNECTION_LOGS.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'LDAP_VERBOSE_CONNECTION_LOGS',
    'variable' => '$LDAP_VERBOSE_CONNECTION_LOGS'
  ),

  'SESSION_DEBUG' => array(
    'category' => 'debug',
    'description' => t('config.SESSION_DEBUG.desc'),
    'help' => t('config.SESSION_DEBUG.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'SESSION_DEBUG',
    'variable' => '$SESSION_DEBUG'
  ),

  'SHOW_ERROR_DETAILS' => array(
    'category' => 'debug',
    'description' => t('config.SHOW_ERROR_DETAILS.desc'),
    'help' => t('config.SHOW_ERROR_DETAILS.help'),
    'type' => 'boolean',
    'default' => false,
    'mandatory' => false,
    'env_var' => 'SHOW_ERROR_DETAILS',
    'variable' => '$SHOW_ERROR_DETAILS'
  ),

  'SMTP_LOG_LEVEL' => array(
    'category' => 'debug',
    'description' => t('config.SMTP_LOG_LEVEL.desc'),
    'help' => t('config.SMTP_LOG_LEVEL.help'),
    'type' => 'integer',
    'default' => 0,
    'mandatory' => false,
    'env_var' => 'SMTP_LOG_LEVEL',
    'variable' => '$SMTP[\'debug_level\']'
  ),

);

##############################################################################
# VARIABLE INITIALISATION
# Set actual PHP variables from environment, using registry defaults
##############################################################################

$log_prefix = "";

##############################################################################
# LDAP Directory Settings
##############################################################################

$LDAP['uri'] = getenv('LDAP_URI');
$LDAP['base_dn'] = getenv('LDAP_BASE_DN');
$LDAP['admin_bind_dn'] = getenv('LDAP_ADMIN_BIND_DN');
$LDAP['admin_bind_pwd'] = getenv('LDAP_ADMIN_BIND_PWD');
$LDAP['connection_type'] = "plain";

$LDAP['require_starttls'] = ((strcasecmp(getenv('LDAP_REQUIRE_STARTTLS'),'TRUE') == 0) ? TRUE : get_config_default('LDAP_REQUIRE_STARTTLS'));
$LDAP['ignore_cert_errors'] = ((strcasecmp(getenv('LDAP_IGNORE_CERT_ERRORS'),'TRUE') == 0) ? TRUE : get_config_default('LDAP_IGNORE_CERT_ERRORS'));
$LDAP['rfc2307bis_check_run'] = FALSE;

$LDAP['admins_group'] = (getenv('LDAP_ADMINS_GROUP') ? getenv('LDAP_ADMINS_GROUP') : get_config_default('LDAP_ADMINS_GROUP'));
$LDAP['group_ou'] = (getenv('LDAP_GROUP_OU') ? getenv('LDAP_GROUP_OU') : get_config_default('LDAP_GROUP_OU'));
$LDAP['user_ou'] = (getenv('LDAP_USER_OU') ? getenv('LDAP_USER_OU') : get_config_default('LDAP_USER_OU'));
$LDAP['forced_rfc2307bis'] = ((strcasecmp(getenv('FORCE_RFC2307BIS'),'TRUE') == 0) ? TRUE : get_config_default('FORCE_RFC2307BIS'));

$LDAP['account_attribute'] = (getenv('LDAP_ACCOUNT_ATTRIBUTE') ? getenv('LDAP_ACCOUNT_ATTRIBUTE') : get_config_default('LDAP_ACCOUNT_ATTRIBUTE'));
$LDAP['group_attribute'] = (getenv('LDAP_GROUP_ATTRIBUTE') ? getenv('LDAP_GROUP_ATTRIBUTE') : get_config_default('LDAP_GROUP_ATTRIBUTE'));

// Computed DN values
$LDAP['group_dn'] = "ou={$LDAP['group_ou']},{$LDAP['base_dn']}";
$LDAP['user_dn'] = "ou={$LDAP['user_ou']},{$LDAP['base_dn']}";

// Object classes and attributes
$LDAP['account_objectclasses'] = array( 'person', 'inetOrgPerson', 'posixAccount' );
$LDAP['group_objectclasses'] = array( 'top', 'posixGroup' );

$LDAP['default_attribute_map'] = array(
  "givenname" => array(
    "label" => t('attr.givenname'),
    "onkeyup" => "update_username(); update_email(); update_cn(); update_homedir(); check_email_validity(document.getElementById('mail').value);",
    "required" => TRUE,
  ),
  "sn" => array(
    "label" => t('attr.sn'),
    "onkeyup" => "update_username(); update_email(); update_cn(); update_homedir(); check_email_validity(document.getElementById('mail').value);",
    "required" => TRUE,
  ),
  "uid" => array(
    "label" => t('attr.uid'),
    "onkeyup" => "check_entity_name_validity(document.getElementById('uid').value,'uid_div'); update_email(); update_homedir(); check_email_validity(document.getElementById('mail').value);",
  ),
  "cn" => array(
    "label" => t('attr.cn'),
    "onkeyup" => "auto_cn_update = false;",
  ),
  "mail" => array(
    "label" => t('attr.mail'),
    "onkeyup" => "auto_email_update = false; check_email_validity(document.getElementById('mail').value);",
  )
);

$LDAP['default_group_attribute_map'] = array( "description" => array("label" => t('groups.col_description')));

// Additional object classes and attributes
if (getenv('LDAP_ACCOUNT_ADDITIONAL_OBJECTCLASSES')) {
  $account_additional_objectclasses = strtolower(getenv('LDAP_ACCOUNT_ADDITIONAL_OBJECTCLASSES'));
  $LDAP['account_objectclasses'] = array_merge($LDAP['account_objectclasses'], explode(",", $account_additional_objectclasses));
}
if (getenv('LDAP_ACCOUNT_ADDITIONAL_ATTRIBUTES')) {
  $LDAP['account_additional_attributes'] = getenv('LDAP_ACCOUNT_ADDITIONAL_ATTRIBUTES');
}

if (getenv('LDAP_GROUP_ADDITIONAL_OBJECTCLASSES')) {
  $group_additional_objectclasses = getenv('LDAP_GROUP_ADDITIONAL_OBJECTCLASSES');
  $LDAP['group_objectclasses'] = array_merge($LDAP['group_objectclasses'], explode(",", $group_additional_objectclasses));
}
if (getenv('LDAP_GROUP_ADDITIONAL_ATTRIBUTES')) {
  $LDAP['group_additional_attributes'] = getenv('LDAP_GROUP_ADDITIONAL_ATTRIBUTES');
}

// Admin can explicitly override membership attribute
// If not set, it will be auto-configured by ldap_detect_rfc2307bis() based on schema detection
if (getenv('LDAP_GROUP_MEMBERSHIP_ATTRIBUTE')) {
  $LDAP['group_membership_attribute'] = getenv('LDAP_GROUP_MEMBERSHIP_ATTRIBUTE');
}

// Admin can explicitly override whether membership uses UID or DN
// If not set, it will be auto-configured by ldap_detect_rfc2307bis() based on membership attribute
if (getenv('LDAP_GROUP_MEMBERSHIP_USES_UID')) {
  if (strtoupper(getenv('LDAP_GROUP_MEMBERSHIP_USES_UID')) == 'TRUE') { $LDAP['group_membership_uses_uid'] = TRUE; }
  if (strtoupper(getenv('LDAP_GROUP_MEMBERSHIP_USES_UID')) == 'FALSE') { $LDAP['group_membership_uses_uid'] = FALSE; }
}

##############################################################################
# User Account Defaults
##############################################################################

$DEFAULT_USER_GROUP = (getenv('DEFAULT_USER_GROUP') ? getenv('DEFAULT_USER_GROUP') : get_config_default('DEFAULT_USER_GROUP'));
$DEFAULT_USER_SHELL = (getenv('DEFAULT_USER_SHELL') ? getenv('DEFAULT_USER_SHELL') : get_config_default('DEFAULT_USER_SHELL'));

// ENFORCE_USERNAME_VALIDATION with backward compatibility for ENFORCE_SAFE_SYSTEM_NAMES
if (getenv('ENFORCE_USERNAME_VALIDATION') !== false) {
  $ENFORCE_USERNAME_VALIDATION = ((strcasecmp(getenv('ENFORCE_USERNAME_VALIDATION'),'FALSE') == 0) ? FALSE : TRUE);
} elseif (getenv('ENFORCE_SAFE_SYSTEM_NAMES') !== false) {
  $ENFORCE_USERNAME_VALIDATION = ((strcasecmp(getenv('ENFORCE_SAFE_SYSTEM_NAMES'),'FALSE') == 0) ? FALSE : TRUE);
} else {
  $ENFORCE_USERNAME_VALIDATION = get_config_default('ENFORCE_USERNAME_VALIDATION');
}
$ENFORCE_SAFE_SYSTEM_NAMES = $ENFORCE_USERNAME_VALIDATION; // Backward compatibility

$USERNAME_FORMAT = (getenv('USERNAME_FORMAT') ? getenv('USERNAME_FORMAT') : get_config_default('USERNAME_FORMAT'));
$USERNAME_REGEX = (getenv('USERNAME_REGEX') ? getenv('USERNAME_REGEX') : get_config_default('USERNAME_REGEX'));

if (getenv('PASSWORD_HASH')) { $PASSWORD_HASH = strtoupper(getenv('PASSWORD_HASH')); }
$ACCEPT_WEAK_PASSWORDS = ((strcasecmp(getenv('ACCEPT_WEAK_PASSWORDS'),'TRUE') == 0) ? TRUE : get_config_default('ACCEPT_WEAK_PASSWORDS'));

$SHOW_POSIX_ATTRIBUTES = ((strcasecmp(getenv('SHOW_POSIX_ATTRIBUTES'),'TRUE') == 0) ? TRUE : get_config_default('SHOW_POSIX_ATTRIBUTES'));

$min_uid = 2000;
$min_gid = 2000;

// Adjust attribute map based on SHOW_POSIX_ATTRIBUTES
if ($SHOW_POSIX_ATTRIBUTES != TRUE) {
  // Always keep both uid and cn fields visible
  // Users can hide specific fields via LDAP_ACCOUNT_ADDITIONAL_ATTRIBUTES if not needed
  // Fixes #219 - allow both email and UID fields to be shown
} else {
  $LDAP['default_attribute_map']["uidnumber"] = array("label" => "UID");
  $LDAP['default_attribute_map']["gidnumber"] = array("label" => "GID");
  $LDAP['default_attribute_map']["homedirectory"] = array("label" => t('attr.homedirectory'), "onkeyup" => "auto_homedir_update = false;");
  $LDAP['default_attribute_map']["loginshell"] = array("label" => t('attr.loginshell'), "default" => $DEFAULT_USER_SHELL);
  $LDAP['default_group_attribute_map']["gidnumber"] = array("label" => t('attr.gidnumber'));
}

##############################################################################
# Multi-Factor Authentication
##############################################################################

$MFA_FEATURE_ENABLED = ((strcasecmp(getenv('MFA_FEATURE_ENABLED'),'TRUE') == 0) ? TRUE : get_config_default('MFA_FEATURE_ENABLED'));

// Parse MFA_REQUIRED_GROUPS into array
$MFA_REQUIRED_GROUPS = array();
if (getenv('MFA_REQUIRED_GROUPS')) {
  $groups = explode(',', getenv('MFA_REQUIRED_GROUPS'));
  foreach ($groups as $group) {
    $group = trim($group);
    if ($group != '') {
      $MFA_REQUIRED_GROUPS[] = $group;
    }
  }
}

$MFA_GRACE_PERIOD_DAYS = (is_numeric(getenv('MFA_GRACE_PERIOD_DAYS')) ? (int)getenv('MFA_GRACE_PERIOD_DAYS') : get_config_default('MFA_GRACE_PERIOD_DAYS'));
$MFA_TOTP_ISSUER = (getenv('MFA_TOTP_ISSUER') ? getenv('MFA_TOTP_ISSUER') : get_config_default('MFA_TOTP_ISSUER'));

// MFA schema check flags (determined dynamically at runtime)
$MFA_SCHEMA_OK = FALSE;
if ($MFA_FEATURE_ENABLED == TRUE) {
  $MFA_SCHEMA_OK = NULL; // NULL means "not yet checked"
}
$MFA_FULLY_OPERATIONAL = NULL; // Will be set when schema check is performed

// TOTP LDAP Attribute Names
$TOTP_ATTRS = array(
  'secret' => (getenv('TOTP_SECRET_ATTRIBUTE') ? getenv('TOTP_SECRET_ATTRIBUTE') : 'totpSecret'),
  'status' => (getenv('TOTP_STATUS_ATTRIBUTE') ? getenv('TOTP_STATUS_ATTRIBUTE') : 'totpStatus'),
  'enrolled_date' => (getenv('TOTP_ENROLLED_DATE_ATTRIBUTE') ? getenv('TOTP_ENROLLED_DATE_ATTRIBUTE') : 'totpEnrolledDate'),
  'scratch_codes' => (getenv('TOTP_SCRATCH_CODES_ATTRIBUTE') ? getenv('TOTP_SCRATCH_CODES_ATTRIBUTE') : 'totpScratchCode'),
  'objectclass' => (getenv('TOTP_OBJECTCLASS') ? getenv('TOTP_OBJECTCLASS') : 'totpUser')
);

// Group MFA LDAP Attribute Names
$GROUP_MFA_ATTRS = array(
  'objectclass' => (getenv('GROUP_MFA_OBJECTCLASS') ? getenv('GROUP_MFA_OBJECTCLASS') : 'mfaGroup'),
  'required' => (getenv('GROUP_MFA_REQUIRED_ATTRIBUTE') ? getenv('GROUP_MFA_REQUIRED_ATTRIBUTE') : 'mfaRequired'),
  'grace_period' => (getenv('GROUP_MFA_GRACE_PERIOD_ATTRIBUTE') ? getenv('GROUP_MFA_GRACE_PERIOD_ATTRIBUTE') : 'mfaGracePeriodDays')
);

##############################################################################
# Password Reset Settings
##############################################################################

$PASSWORD_RESET_ENABLED = ((strcasecmp(getenv('PASSWORD_RESET_ENABLED'),'TRUE') == 0) ? TRUE : get_config_default('PASSWORD_RESET_ENABLED'));
$USE_LDAP_AS_DB = ((strcasecmp(getenv('USE_LDAP_AS_DB'),'TRUE') == 0) ? TRUE : get_config_default('USE_LDAP_AS_DB'));

$PASSWORD_RESET_TOKEN_EXPIRY_MINUTES = (is_numeric(getenv('PASSWORD_RESET_TOKEN_EXPIRY_MINUTES')) ? (int)getenv('PASSWORD_RESET_TOKEN_EXPIRY_MINUTES') : get_config_default('PASSWORD_RESET_TOKEN_EXPIRY_MINUTES'));
$PASSWORD_RESET_RATE_LIMIT_REQUESTS = (is_numeric(getenv('PASSWORD_RESET_RATE_LIMIT_REQUESTS')) ? (int)getenv('PASSWORD_RESET_RATE_LIMIT_REQUESTS') : get_config_default('PASSWORD_RESET_RATE_LIMIT_REQUESTS'));
$PASSWORD_RESET_RATE_LIMIT_WINDOW_MINUTES = (is_numeric(getenv('PASSWORD_RESET_RATE_LIMIT_WINDOW_MINUTES')) ? (int)getenv('PASSWORD_RESET_RATE_LIMIT_WINDOW_MINUTES') : get_config_default('PASSWORD_RESET_RATE_LIMIT_WINDOW_MINUTES'));
$PASSWORD_RESET_MAX_ATTEMPTS = (is_numeric(getenv('PASSWORD_RESET_MAX_ATTEMPTS')) ? (int)getenv('PASSWORD_RESET_MAX_ATTEMPTS') : get_config_default('PASSWORD_RESET_MAX_ATTEMPTS'));
$PASSWORD_RESET_LOCKOUT_DURATION_MINUTES = (is_numeric(getenv('PASSWORD_RESET_LOCKOUT_DURATION_MINUTES')) ? (int)getenv('PASSWORD_RESET_LOCKOUT_DURATION_MINUTES') : get_config_default('PASSWORD_RESET_LOCKOUT_DURATION_MINUTES'));

##############################################################################
# User Profile Settings
##############################################################################

// Default user-editable attributes (essential contact info and personal details only)
$DEFAULT_USER_EDITABLE_ATTRIBUTES = get_config_default('DEFAULT_USER_EDITABLE_ATTRIBUTES');

// Admin-configured additional editable attributes
$ADMIN_USER_EDITABLE_ATTRIBUTES = array();
if (getenv('USER_EDITABLE_ATTRIBUTES')) {
  $attrs = explode(',', getenv('USER_EDITABLE_ATTRIBUTES'));
  foreach ($attrs as $attr) {
    $attr = trim(strtolower($attr));
    if ($attr != '') {
      $ADMIN_USER_EDITABLE_ATTRIBUTES[] = $attr;
    }
  }
}

// Merge default and admin-configured attributes
$USER_EDITABLE_ATTRIBUTES = array_unique(array_merge($DEFAULT_USER_EDITABLE_ATTRIBUTES, $ADMIN_USER_EDITABLE_ATTRIBUTES));

// Security blacklist: Attributes that users must NEVER be allowed to edit
$ATTRIBUTE_BLACKLIST = get_config_default('ATTRIBUTE_BLACKLIST');

/**
 * Check if an attribute is safe for users to edit
 */
function is_user_editable($attribute) {
  global $ATTRIBUTE_BLACKLIST, $USER_EDITABLE_ATTRIBUTES;
  $attribute_lower = strtolower(trim($attribute));
  if ($attribute_lower == '') { return FALSE; }
  if (in_array($attribute_lower, array_map('strtolower', $ATTRIBUTE_BLACKLIST))) { return FALSE; }
  if (in_array($attribute_lower, array_map('strtolower', $USER_EDITABLE_ATTRIBUTES))) { return TRUE; }
  return FALSE;
}

##############################################################################
# Email Settings
##############################################################################

$SMTP['host'] = getenv('SMTP_HOSTNAME');
$SMTP['port'] = (is_numeric(getenv('SMTP_HOST_PORT')) ? getenv('SMTP_HOST_PORT') : get_config_default('SMTP_HOST_PORT'));
$SMTP['user'] = getenv('SMTP_USERNAME');
$SMTP['pass'] = getenv('SMTP_PASSWORD');

$SMTP['tls'] = ((strcasecmp(getenv('SMTP_USE_TLS'),'TRUE') == 0) ? TRUE : get_config_default('SMTP_USE_TLS'));
$SMTP['ssl'] = ((strcasecmp(getenv('SMTP_USE_SSL'),'TRUE') == 0) ? TRUE : get_config_default('SMTP_USE_SSL'));
$SMTP['helo'] = getenv('SMTP_HELO_HOST');

$EMAIL['from_address'] = (getenv('EMAIL_FROM_ADDRESS') ? getenv('EMAIL_FROM_ADDRESS') : get_config_default('EMAIL_FROM_ADDRESS'));
$EMAIL['from_name'] = (getenv('EMAIL_FROM_NAME') ? getenv('EMAIL_FROM_NAME') : get_config_default('EMAIL_FROM_NAME'));
$EMAIL['reply_to_address'] = (getenv('EMAIL_REPLY_TO_ADDRESS') ? getenv('EMAIL_REPLY_TO_ADDRESS') : get_config_default('EMAIL_REPLY_TO_ADDRESS'));
$EMAIL_DOMAIN = getenv('EMAIL_DOMAIN');

$EMAIL_SENDING_ENABLED = (!empty($SMTP['host']));

$EMAIL_USER_ON_PASSWORD_CHANGE = ((strcasecmp(getenv('EMAIL_USER_ON_PASSWORD_CHANGE'),'TRUE') == 0) ? TRUE : get_config_default('EMAIL_USER_ON_PASSWORD_CHANGE'));
$EMAIL_ADMIN_ON_USER_PASSWORD_CHANGE = ((strcasecmp(getenv('EMAIL_ADMIN_ON_USER_PASSWORD_CHANGE'),'TRUE') == 0) ? TRUE : get_config_default('EMAIL_ADMIN_ON_USER_PASSWORD_CHANGE'));
$ADMIN_EMAIL = (getenv('ADMIN_EMAIL') ? getenv('ADMIN_EMAIL') : get_config_default('ADMIN_EMAIL'));

$ACCOUNT_REQUESTS_ENABLED = ((strcasecmp(getenv('ACCOUNT_REQUESTS_ENABLED'),'TRUE') == 0) ? TRUE : get_config_default('ACCOUNT_REQUESTS_ENABLED'));
// Account requests email with fallback to ADMIN_EMAIL
$ACCOUNT_REQUESTS_EMAIL = getenv('ACCOUNT_REQUESTS_EMAIL');
if (!$ACCOUNT_REQUESTS_EMAIL) {
  $ACCOUNT_REQUESTS_EMAIL = $ADMIN_EMAIL; // Fall back to admin email
}
if (!$ACCOUNT_REQUESTS_EMAIL) {
  $ACCOUNT_REQUESTS_EMAIL = get_config_default('ACCOUNT_REQUESTS_EMAIL');
}

##############################################################################
# Interface & Branding
##############################################################################

$ORGANISATION_NAME = (getenv('ORGANISATION_NAME') ? getenv('ORGANISATION_NAME') : get_config_default('ORGANISATION_NAME'));
$SITE_NAME = (getenv('SITE_NAME') ? getenv('SITE_NAME') : $ORGANISATION_NAME);

$SERVER_HOSTNAME = (getenv('SERVER_HOSTNAME') ? getenv('SERVER_HOSTNAME') : get_config_default('SERVER_HOSTNAME'));
$NO_HTTPS = ((strcasecmp(getenv('NO_HTTPS'),'TRUE') == 0) ? TRUE : get_config_default('NO_HTTPS'));
$SERVER_PATH = (getenv('SERVER_PATH') ? getenv('SERVER_PATH') : get_config_default('SERVER_PATH'));

$SITE_LOGIN_LDAP_ATTRIBUTE = (getenv('SITE_LOGIN_LDAP_ATTRIBUTE') ? getenv('SITE_LOGIN_LDAP_ATTRIBUTE') : $LDAP['account_attribute']);
$SITE_LOGIN_FIELD_LABEL = (getenv('SITE_LOGIN_FIELD_LABEL') ? getenv('SITE_LOGIN_FIELD_LABEL') : get_config_default('SITE_LOGIN_FIELD_LABEL'));

$CUSTOM_LOGO = (getenv('CUSTOM_LOGO') ? getenv('CUSTOM_LOGO') : FALSE);
$CUSTOM_STYLES = (getenv('CUSTOM_STYLES') ? getenv('CUSTOM_STYLES') : FALSE);
$PAGINATION_ITEMS_PER_PAGE = (is_numeric(getenv('PAGINATION_ITEMS_PER_PAGE')) ? (int)getenv('PAGINATION_ITEMS_PER_PAGE') : get_config_default('PAGINATION_ITEMS_PER_PAGE'));

##############################################################################
# Session & Security
##############################################################################

$SESSION_TIMEOUT = (is_numeric(getenv('SESSION_TIMEOUT')) ? (int)getenv('SESSION_TIMEOUT') : get_config_default('SESSION_TIMEOUT'));
$REMOTE_HTTP_HEADERS_LOGIN = ((strcasecmp(getenv('REMOTE_HTTP_HEADERS_LOGIN'),'TRUE') == 0) ? TRUE : get_config_default('REMOTE_HTTP_HEADERS_LOGIN'));

##############################################################################
# Debug & Logging
##############################################################################

$LDAP_DEBUG = ((strcasecmp(getenv('LDAP_DEBUG'),'TRUE') == 0) ? TRUE : get_config_default('LDAP_DEBUG'));
$LDAP_VERBOSE_CONNECTION_LOGS = ((strcasecmp(getenv('LDAP_VERBOSE_CONNECTION_LOGS'),'TRUE') == 0) ? TRUE : get_config_default('LDAP_VERBOSE_CONNECTION_LOGS'));
$SESSION_DEBUG = ((strcasecmp(getenv('SESSION_DEBUG'),'TRUE') == 0) ? TRUE : get_config_default('SESSION_DEBUG'));
$SHOW_ERROR_DETAILS = ((strcasecmp(getenv('SHOW_ERROR_DETAILS'),'TRUE') == 0) ? TRUE : get_config_default('SHOW_ERROR_DETAILS'));

$SMTP['debug_level'] = getenv('SMTP_LOG_LEVEL');
if (!is_numeric($SMTP['debug_level']) or $SMTP['debug_level'] > 4 or $SMTP['debug_level'] < 0) {
  $SMTP['debug_level'] = get_config_default('SMTP_LOG_LEVEL');
}

##############################################################################
# Audit Logging
##############################################################################

$AUDIT_ENABLED = ((strcasecmp(getenv('AUDIT_ENABLED'),'TRUE') == 0) ? TRUE : get_config_default('AUDIT_ENABLED'));
$AUDIT_LOG_FILE = (getenv('AUDIT_LOG_FILE') ? getenv('AUDIT_LOG_FILE') : get_config_default('AUDIT_LOG_FILE'));
$AUDIT_LOG_RETENTION_DAYS = (is_numeric(getenv('AUDIT_LOG_RETENTION_DAYS')) ? (int)getenv('AUDIT_LOG_RETENTION_DAYS') : get_config_default('AUDIT_LOG_RETENTION_DAYS'));

##############################################################################
# Password Policy
##############################################################################

$PASSWORD_POLICY_ENABLED = ((strcasecmp(getenv('PASSWORD_POLICY_ENABLED'),'TRUE') == 0) ? TRUE : get_config_default('PASSWORD_POLICY_ENABLED'));
$PPOLICY_ENABLED = ((strcasecmp(getenv('PPOLICY_ENABLED'),'TRUE') == 0) ? TRUE : get_config_default('PPOLICY_ENABLED'));
$PASSWORD_MIN_LENGTH = (is_numeric(getenv('PASSWORD_MIN_LENGTH')) ? (int)getenv('PASSWORD_MIN_LENGTH') : get_config_default('PASSWORD_MIN_LENGTH'));
$PASSWORD_REQUIRE_UPPERCASE = ((strcasecmp(getenv('PASSWORD_REQUIRE_UPPERCASE'),'TRUE') == 0) ? TRUE : get_config_default('PASSWORD_REQUIRE_UPPERCASE'));
$PASSWORD_REQUIRE_LOWERCASE = ((strcasecmp(getenv('PASSWORD_REQUIRE_LOWERCASE'),'TRUE') == 0) ? TRUE : get_config_default('PASSWORD_REQUIRE_LOWERCASE'));
$PASSWORD_REQUIRE_NUMBERS = ((strcasecmp(getenv('PASSWORD_REQUIRE_NUMBERS'),'TRUE') == 0) ? TRUE : get_config_default('PASSWORD_REQUIRE_NUMBERS'));
$PASSWORD_REQUIRE_SPECIAL = ((strcasecmp(getenv('PASSWORD_REQUIRE_SPECIAL'),'TRUE') == 0) ? TRUE : get_config_default('PASSWORD_REQUIRE_SPECIAL'));
$PASSWORD_MIN_SCORE = (is_numeric(getenv('PASSWORD_MIN_SCORE')) ? (int)getenv('PASSWORD_MIN_SCORE') : get_config_default('PASSWORD_MIN_SCORE'));
$PASSWORD_HISTORY_COUNT = (is_numeric(getenv('PASSWORD_HISTORY_COUNT')) ? (int)getenv('PASSWORD_HISTORY_COUNT') : get_config_default('PASSWORD_HISTORY_COUNT'));
$PASSWORD_EXPIRY_DAYS = (is_numeric(getenv('PASSWORD_EXPIRY_DAYS')) ? (int)getenv('PASSWORD_EXPIRY_DAYS') : get_config_default('PASSWORD_EXPIRY_DAYS'));
$PASSWORD_EXPIRY_WARNING_DAYS = (is_numeric(getenv('PASSWORD_EXPIRY_WARNING_DAYS')) ? (int)getenv('PASSWORD_EXPIRY_WARNING_DAYS') : get_config_default('PASSWORD_EXPIRY_WARNING_DAYS'));

##############################################################################
# Account Lifecycle
##############################################################################

$LIFECYCLE_ENABLED = ((strcasecmp(getenv('LIFECYCLE_ENABLED'),'TRUE') == 0) ? TRUE : get_config_default('LIFECYCLE_ENABLED'));
$ACCOUNT_EXPIRY_ENABLED = ((strcasecmp(getenv('ACCOUNT_EXPIRY_ENABLED'),'TRUE') == 0) ? TRUE : get_config_default('ACCOUNT_EXPIRY_ENABLED'));
$ACCOUNT_INACTIVE_DAYS = (is_numeric(getenv('ACCOUNT_INACTIVE_DAYS')) ? (int)getenv('ACCOUNT_INACTIVE_DAYS') : get_config_default('ACCOUNT_INACTIVE_DAYS'));
$ACCOUNT_EXPIRY_WARNING_DAYS = (is_numeric(getenv('ACCOUNT_EXPIRY_WARNING_DAYS')) ? (int)getenv('ACCOUNT_EXPIRY_WARNING_DAYS') : get_config_default('ACCOUNT_EXPIRY_WARNING_DAYS'));
$ACCOUNT_CLEANUP_ENABLED = ((strcasecmp(getenv('ACCOUNT_CLEANUP_ENABLED'),'TRUE') == 0) ? TRUE : get_config_default('ACCOUNT_CLEANUP_ENABLED'));

##############################################################################
# HELPER FUNCTIONS
##############################################################################

/**
 * Get the default value for a configuration key from the registry
 *
 * @param string $key Configuration key (e.g., 'LDAP_URI')
 * @return mixed Default value from registry
 */
function get_config_default($key) {
  global $CONFIG_REGISTRY;
  return isset($CONFIG_REGISTRY[$key]) ? $CONFIG_REGISTRY[$key]['default'] : null;
}

/**
 * Get the current value of a configuration variable
 *
 * @param string $variable Variable path (e.g., '$LDAP[\'uri\']' or '$DEFAULT_USER_SHELL')
 * @return mixed Current value
 */
function get_config_value($variable) {
  // Extract variable name from path
  if (preg_match('/\$(\w+)\[\'(\w+)\'\]/', $variable, $matches)) {
    // Array access: $LDAP['uri']
    $array_name = $matches[1];
    $key = $matches[2];
    global $$array_name;
    return isset($$array_name[$key]) ? $$array_name[$key] : null;
  } elseif (preg_match('/\$(\w+)/', $variable, $matches)) {
    // Simple variable: $DEFAULT_USER_SHELL
    $var_name = $matches[1];
    global $$var_name;
    return isset($$var_name) ? $$var_name : null;
  }
  return null;
}

/**
 * Get metadata for a configuration key
 *
 * @param string $key Configuration key
 * @return array|null Configuration metadata
 */
function get_config_metadata($key) {
  global $CONFIG_REGISTRY;
  return isset($CONFIG_REGISTRY[$key]) ? $CONFIG_REGISTRY[$key] : null;
}

/**
 * Get all configurations in a category
 *
 * @param string $category Category key
 * @return array Configurations in the category
 */
function get_configs_by_category($category) {
  global $CONFIG_REGISTRY;
  $configs = array();

  foreach ($CONFIG_REGISTRY as $key => $config) {
    if ($config['category'] === $category) {
      $configs[$key] = $config;
    }
  }

  return $configs;
}

/**
 * Check if a configuration value is set to its default
 *
 * @param string $key Configuration key
 * @return bool TRUE if current value equals default
 */
function is_config_default($key) {
  $config = get_config_metadata($key);
  if (!$config) {
    return false;
  }

  $current = get_config_value($config['variable']);
  return ($current === $config['default']);
}

/**
 * Get all configuration categories sorted by order
 *
 * @return array Categories sorted by order
 */
function get_config_categories() {
  global $CONFIG_CATEGORIES;

  $categories = $CONFIG_CATEGORIES;
  uasort($categories, function($a, $b) {
    return $a['order'] - $b['order'];
  });

  return $categories;
}

/**
 * Check if a configuration category has any non-default values
 *
 * @param string $category Category key
 * @return bool TRUE if any config in category is non-default
 */
function category_has_changes($category) {
  $configs = get_configs_by_category($category);

  foreach (array_keys($configs) as $key) {
    if (!is_config_default($key)) {
      return true;
    }
  }

  return false;
}


