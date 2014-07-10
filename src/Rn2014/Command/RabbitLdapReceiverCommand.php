<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 04:44
 */

namespace Rn2014\Command;

use Rn2014\Ldap\LdapCommander;
use Rn2014\Ldap\LdapRawCaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
                'ldap_receiver'
            )
            ->addOption(
                'stop-word',
                'x',
                InputOption::VALUE_REQUIRED,
                'Quante è la parola per terminare?',
                'quit'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue_name = $input->getArgument('queue_name');
        $consumer_tag = $input->getArgument('consumer_tag');
        $stop_word = $input->getOption('stop-word');

// server
        $host = RABBITMQ_HOST;
        $port = RABBITMQ_PORT;
        $user = RABBITMQ_USER;
        $password = RABBITMQ_PASS;

        $ldapReceiver = new LdapReceiver($this->getApplication(), $output);

        $connection = new AMQPConnection($host, $port, $user, $password);
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