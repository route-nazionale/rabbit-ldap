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

class RabbitLdapSetupCommand extends Command
{
    public function __construct($rabbit, $name = null)
    {
        $this->rabbit = $rabbit;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('rabbit:ldap:setup')
            ->setDescription('dichiara la coda ldap e la collega all\'exchange')
            ->addArgument(
                'exchange_name',
                InputArgument::OPTIONAL,
                'Nome dell\'exchange',
                'application'

            )
            ->addArgument(
                'queue_name',
                InputArgument::OPTIONAL,
                'Nome della coda',
                'ldap'
            )
            ->addArgument(
                'binding_key',
                InputArgument::OPTIONAL,
                'Binding key',
                'humen.*'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exchange_name  = $input->getArgument('exchange_name');
        $queue_name = $input->getArgument('queue_name');
        $binding_key = $input->getArgument('binding_key');

// RECEIVER SPECIFIC

        $exclusive = false;
        $passive = false;
        $durable = true;
        $auto_delete = false;
//        $binding_key = 'humen.*';

        $output->writeln("connection opened");

        $channel = $this->rabbit->channel();
        $output->writeln("channel opened");
        $channel->queue_declare($queue_name, $passive, $durable, $exclusive, $auto_delete);
        $output->writeln("queue declared");

// collego coda ad exchange in modo permanente
        $channel->queue_bind($queue_name, $exchange_name, $binding_key);
        $output->writeln("queue [$queue_name] binded to exchanger [$exchange_name]");

    }
}