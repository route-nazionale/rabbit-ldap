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
use PhpAmqpLib\Message\AMQPMessage;

class RabbitTestSendCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('rabbit:test:send')
            ->setDescription('spedisce un messaggio di test all\'exchange')
            ->addArgument(
                'exchange_name',
                InputArgument::OPTIONAL,
                'Nome dell\'exchange',
                'application'

            )
            ->addArgument(
                'routing_key',
                InputArgument::OPTIONAL,
                'Routing key',
                'humen.insert'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exchange_name  = $input->getArgument('exchange_name');
        $routing_key = $input->getArgument('routing_key');

// server
        $host = RABBITMQ_HOST;
        $port = RABBITMQ_PORT;
        $user = RABBITMQ_USER;
        $password = RABBITMQ_PASS;
        $vhost = RABBITMQ_VHOST;

// sender specific

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

        $user = new \StdClass;
        $user->name = "prova prova";
        $user->username = "testlancio";
        $user->password = "123123123";
        $user->type= "test";

        $msg = $this->createMessage($user);

        $channel->basic_publish($msg, $exchange_name, $routing_key);

        $output->writeln(" [x] Sent ".$routing_key,':'.$msg->body);

        $channel->close();
        $output->writeln("channel closed");
        $connection->close();
        $output->writeln("connection closed");
    }

    protected function createMessage($message)
    {
        $data = json_encode($message);
        $amqpMessage = new AMQPMessage($data);
        return $amqpMessage;
    }
}