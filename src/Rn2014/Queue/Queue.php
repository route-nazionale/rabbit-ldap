<?php
/**
 * User: lancio
 * Date: 01/07/14
 * Time: 10:34
 */

namespace Rn2014;

use PhpAmqpLib\Channel\AMQPChannel;

class Queue
{
    public function __construct($queueName = "msgs", AMQPChannel $ch)
    {
        $this->queue = $queueName;

        $this->exchange = 'router';

        $this->ch = $ch;

        $this->ch->queue_declare($this->queue, false, true, false, false);

        $this->ch->exchange_declare($this->exchange, 'direct', false, true, false);

        $this->ch->queue_bind($this->queue, $this->exchange);
    }

    public function add($msg)
    {
        $this->ch->basic_publish($msg, $this->exchange);

    }

    public function addConsumer($callback)
    {
        $consumer_tag = 'consumer';
        $this->ch->basic_consume($this->queue, $consumer_tag, false, false, false, false, $callback);
    }

    public function getCallbacks()
    {
        return $this->ch->callbacks;
    }

    public function wait()
    {
        return $this->ch->wait();
    }

}