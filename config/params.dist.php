<?php
/**
 * User: lancio
 * Date: 01/07/14
 * Time: 01:56
 */

define('HTTPS_REQUIRED', false);

define('RABBITMQ_HOST', 'localhost');
define('RABBITMQ_PORT', 5672);
define('RABBITMQ_USER', 'guest');
define('RABBITMQ_PASS', 'guest');
define('RABBITMQ_VHOST', '/');

//If this is enabled you can see AMQP output on the CLI
define('AMQP_DEBUG', false);


define("LDAP_HOST", "localhost");
define("LDAP_PORT", 636);
define("LDAP_SECURITY", "SSL");
define('LDAP_VERSION', 3);
define('LDAP_BASE_DN', 'dc=example,dc=com');

define('LDAP_ADMIN_DN', "cn=admin,dc=example,dc=com");
define('LDAP_ADMIN_PASSWORD', "password");

/**
 * percorso degli script add_[group]_user.sh
 */
define('LDAP_PATH_SCRIPTS', './bin/');


/*
 * se non sono presenti le cerca su db
 * iv e key di decrypt
 */
define('AES_IV', ''); //iv 16byte
define('AES_KEY', ''); //key 32byte

/**
 * accesso al db per prelevare iv e key di decrypt
 */
define('MYSQL_HOST', 'localhost');
define('MYSQL_PORT', '3662');
define('MYSQL_DB', 'database');
define('MYSQL_USER', 'username');
define('MYSQL_PASS', 'password');

