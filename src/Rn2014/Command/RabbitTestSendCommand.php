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
                'humen.change'
            )
            ->addOption(
                'stop-word',
                'x',
                InputOption::VALUE_REQUIRED,
                'Inserire la parola corretta per terminare il receiver',
                false
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exchange_name  = $input->getArgument('exchange_name');
        $routing_key = $input->getArgument('routing_key');
        $stop_word = $input->getOption('stop-word');

// server
        $host = RABBITMQ_HOST;
        $port = RABBITMQ_PORT;
        $user = RABBITMQ_USER;
        $password = RABBITMQ_PASS;

// sender specific

        $connection = new AMQPConnection($host, $port, $user, $password);
        $output->writeln("connection opened");

        $channel = $connection->channel();
        $output->writeln("channel opened");

        if ($stop_word ) {

            $msg = $this->createMessage($stop_word);
        } else {
            $utente = new \StdClass;
            $utente->nome = "prova";
            $utente->cognome = "gracco";

            $msg = $this->createMessage($utente);
        }


        $channel->basic_publish($msg, $exchange_name, $routing_key);

        $output->writeln(" [x] Sent ".$routing_key,':'.$msg->body);

        $channel->close();
        $output->writeln("channel closed");
        $connection->close();
        $output->writeln("connection closed");
    }

    protected function createMessage($utente)
    {
        $data = json_encode($utente);
        $message = new AMQPMessage($data);
        return $message;
    }
}