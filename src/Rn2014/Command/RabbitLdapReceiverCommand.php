<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 04:44
 */

namespace Rn2014\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;

use Rn2014\AESEncoder;
use Rn2014\Queue\LdapReceiver;

class RabbitLdapReceiverCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('rabbit:ldap:receiver')
            ->setDescription('avvia l\'ascoltatore della coda ldap')
            ->addArgument(
                'queue_name',
                InputArgument::OPTIONAL,
                'Nome della coda su cui ascoltare',
                'ldap'

            )
            ->addArgument(
                'consumer_tag',
                InputArgument::OPTIONAL,
                'Consumer tag',
                'humen.insert'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue_name = $input->getArgument('queue_name');
        $consumer_tag = $input->getArgument('consumer_tag');

// server
        $host = RABBITMQ_HOST;
        $port = RABBITMQ_PORT;
        $user = RABBITMQ_USER;
        $password = RABBITMQ_PASS;
        $vhost = RABBITMQ_VHOST;

        if (AES_IV && AES_KEY) {

            $iv = AES_IV;
            $key = AES_KEY;

        } else {

            $config = new \Doctrine\DBAL\Configuration();

            $connectionParams = array(
                'dbname' => MYSQL_DB,
                'user' => MYSQL_USER,
                'password' => MYSQL_PASS,
                'host' => MYSQL_HOST,
                'port' => MYSQL_PORT,
                'charset'     => 'utf8',
                'driver' => 'pdo_mysql',
            );
            $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

            $sql = "SELECT * FROM aes LIMIT 1";
            $cryptData = $conn->fetchAssoc($sql);

            if (!$cryptData) {
                throw new \Exception("key and iv not found");
            }

            $iv = base64_decode($cryptData['iv']);
            $key = base64_decode($cryptData['key']);
        }

        $aesEncoder = new AESEncoder($key, $iv);

        $ldapReceiver = new LdapReceiver($this->getApplication(), $output, $aesEncoder);

        if (RABBITMQ_SSL) {

            $ssl_options = array(
                'capath' => RABBITMQ_SSL_CAPATH,
                'cafile' => RABBITMQ_SSL_CAFILE,
                'verify_peer' => RABBITMQ_SSL_VERIFY_PEER,
            );

            $connection = new AMQPSSLConnection($host, $port, $user, $password, $vhost, $ssl_options);

        } else {
            $connection = new AMQPConnection($host, $port, $user, $password, $vhost);
        }

        $output->writeln("connection opened");

        $channel = $connection->channel();
        $output->writeln("channel opened");

        /* $prefetch_size, $prefetch_count, $a_global */
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume($queue_name, $consumer_tag, false, false, false, false, [$ldapReceiver, 'processMessage']);

        register_shutdown_function(['Rn2014\Queue\LdapReceiver', 'shutdown'], $channel, $connection);

        // Loop as long as the channel has callbacks registered
        while(count($channel->callbacks)) {
            $output->writeln("message received");
            $channel->wait();
        }

        $output->writeln("channel closed");
        $output->writeln("connection closed");
    }
}