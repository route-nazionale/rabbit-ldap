<?php
/**
 * User: lancio
 * Date: 01/07/14
 * Time: 01:56
 */

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
