<?php
/**
 * User: lancio
 * Date: 01/07/14
 * Time: 01:56
 */

define('APP_DEBUG', false);

define('HTTPS_REQUIRED', false);

/**
 * (ldap|db)
 * definisce se utilizzare ldap o temp (db temporaneo) per controllare i dati per l'autenticazione
 */
define('LOGIN_METHOD', 'ldap');

/**
 * parametri di rabbitmq
 */
define('RABBITMQ_HOST', 'localhost');
define('RABBITMQ_PORT', 5672);
define('RABBITMQ_USER', 'guest');
define('RABBITMQ_PASS', 'guest');
define('RABBITMQ_VHOST', '/');

define('RABBITMQ_SSL', false);
define('RABBITMQ_SSL_CAPATH', '/path/to/certs');
define('RABBITMQ_SSL_CAFILE', '/path/to/cacert.pem');
define('RABBITMQ_SSL_VERIFY_PEER', true);

//If this is enabled you can see AMQP output on the CLI
define('AMQP_DEBUG', false);

/**
 * parametri di ldap
 */
define("LDAP_HOST", "localhost");
define("LDAP_PORT", 636);
define("LDAP_SECURITY", "SSL");
define('LDAP_VERSION', 3);
define('LDAP_BASE_DN', 'dc=example,dc=com');

define('LDAP_ADMIN_DN', "cn=admin,dc=example,dc=com");
define('LDAP_ADMIN_PASSWORD', "password");

/**
 * percorso degli script add_[group]_user.sh
 * utilizzati per creare gli utenti
 */
define('LDAP_PATH_SCRIPTS', './bin/');

/*
 * se non sono presenti le cerca su db
 * iv e key di decrypt
 */
define('AES_IV', ''); //iv 16byte
define('AES_KEY', ''); //key 32byte

/**
 * Parametri connessioni mysql
 */
define('MYSQL_HOST', 'localhost');
define('MYSQL_PORT', '3662');

/**
 * AES accesso al db per prelevare iv e key di decrypt
 */
define('MYSQL_DB_AES', 'database');
define('MYSQL_USER_AES', 'username');
define('MYSQL_PASS_AES', 'password');

/**
 * LDAP accesso al db per scrivere i gruppi posix
 */
define('MYSQL_DB_LDAP_POSIX', 'database');
define('MYSQL_USER_LDAP_POSIX', 'username');
define('MYSQL_PASS_LDAP_POSIX', 'password');
/**
 * LDAP accesso temporaneo per l'autenticaione, in attesa di sistemare ldap
 */
define('MYSQL_DB_LDAP', 'database');
define('MYSQL_USER_LDAP', 'username');
define('MYSQL_PASS_LDAP', 'password');

/**
 * DB per l'autenticaione basata su data di nascita
 */
define('MYSQL_DB_AQUILE', 'database');
define('MYSQL_USER_AQUILE', 'username');
define('MYSQL_PASS_AQUILE', 'password');
