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
    public function __construct($rabbit, $encoder, $name = null)
    {
        $this->rabbit = $rabbit;
        $this->encoder = $encoder;

        parent::__construct($name);
    }

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

        $output->writeln("connection opened");

        $channel = $this->rabbit->channel();
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
        $this->rabbit->close();
        $output->writeln("connection closed");
    }

    protected function createMessage($message)
    {
        $data = json_encode($message);
        $amqpMessage = new AMQPMessage($data);
        return $amqpMessage;
    }
}