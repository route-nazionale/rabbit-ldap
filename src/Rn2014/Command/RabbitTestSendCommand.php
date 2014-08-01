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
            ->addOption(
                'exchange_name',
                'e',
                InputOption::VALUE_REQUIRED,
                'Nome dell\'exchange',
                'application'
            )
            ->addOption(
                'routing_key',
                'r',
                InputOption::VALUE_REQUIRED,
                'Routing key',
                'test.send'
            )
            ->addArgument(
                'message',
                InputArgument::REQUIRED,
                'Messaggio da inviare'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exchange_name  = $input->getOption('exchange_name');
        $routing_key = $input->getOption('routing_key');
        $message = $input->getArgument('message');

        $output->writeln("connection opened");

        $channel = $this->rabbit->channel();
        $output->writeln("channel opened");

        $message = json_decode($message);

//        $message = json_decode('[{ "fields": { "max_age": 99, "kind": "LAB", "code": "LAB-A-1-659", "state_chief": "ENABLED", "name": "aaaaaaaaaaaa", "district": "Q1", "max_chiefs_seats": 5, "min_age": 1, "topic": 1, "max_boys_seats": 30, "state_activation": "DISMISSED", "num": 659, "print_code": "ELIMINATO", "min_seats": 1, "seats_tot": 35, "state_handicap": "ENABLED", "state_subscription": "OPEN", "description": "" }, "model": "base.event", "pk": 1145}]');
//var_dump($message);
//die("AA");
//        $user = json_decode(file_get_contents(__DIR__ . "/../../../data/test.user.json"));
//        $user->name = "prova prova";
//        $user->username = "testlancio";
//        $user->password = "123123123";
//        $user->type= "test";

        $msg = $this->createMessage($message);

        $channel->basic_publish($msg, $exchange_name, $routing_key);

        $output->writeln(" [x] Sent ".$routing_key,':'.$msg->body);

        $channel->close();
        $output->writeln("channel closed");
        $this->rabbit->close();
        $output->writeln("connection closed");
    }

    protected function createMessage($message)
    {
        $data = json_encode($message, JSON_PRETTY_PRINT);
        $amqpMessage = new AMQPMessage($data);

        return $amqpMessage;
    }
}

