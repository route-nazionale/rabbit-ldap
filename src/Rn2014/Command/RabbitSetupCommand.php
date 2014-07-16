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

class RabbitSetupCommand extends Command
{
    public function __construct($rabbit, $name = null)
    {
        $this->rabbit = $rabbit;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('rabbit:setup')
            ->setDescription('dichiara l\'exchange')
            ->addArgument(
                'exchange_name',
                InputArgument::OPTIONAL,
                'Nome dell\'exchange',
                'application'

            )
            ->addArgument(
                'exchange_type',
                InputArgument::OPTIONAL,
                'Tipo dell\'exchange',
                'topic'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exchange_name = $input->getArgument('exchange_name');
        $exchange_type = $input->getArgument('exchange_type');

// common
        $passive = false;
        $durable = true;
        $auto_delete = false;

        $output->writeln("connection opened");

        $channel = $this->rabbit->channel();
        $output->writeln("channel opened");

// EXCHANGE DEFINITION
        /*
            name: $exchange
            type: direct
            passive: false
            durable: true // the exchange will survive server restarts
            auto_delete: false //the exchange won't be deleted once the channel is closed.
        */
        $channel->exchange_declare($exchange_name, $exchange_type, $passive, $durable, $auto_delete);
        $output->writeln("exchange declared: name=$exchange_name, type=$exchange_type");

        $channel->close();
        $output->writeln("channel closed");

        $this->rabbit->close();
        $output->writeln("connection closed");

    }
}