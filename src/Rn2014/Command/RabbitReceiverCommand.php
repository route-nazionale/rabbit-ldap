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

use Rn2014\Queue\Receiver;

class RabbitReceiverCommand extends Command
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
            ->setName('rabbit:receiver')
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

        $receiver = new Receiver($this->getApplication(), $output, $this->encoder);

        $output->writeln("connection opened");

        $channel = $this->rabbit->channel();
        $output->writeln("channel opened");

        /* $prefetch_size, $prefetch_count, $a_global */
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume($queue_name, $consumer_tag, false, false, false, false, [$receiver, 'processMessage']);

        register_shutdown_function(['Rn2014\Queue\Receiver', 'shutdown'], $channel, $this->rabbit);

        // Loop as long as the channel has callbacks registered
        while(count($channel->callbacks)) {
            $output->writeln("message received");
            $channel->wait();
        }

        $output->writeln("channel closed");
        $output->writeln("connection closed");
    }
}