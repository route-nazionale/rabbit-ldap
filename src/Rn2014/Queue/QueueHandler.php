<?php
/**
 * User: lancio
 * Date: 01/07/14
 * Time: 10:22
 */
namespace Rn2014;

use PhpAmqpLib\Connection\AMQPConnection;

class QueueHandler
{
    private $conn;

    public function __construct()
    {
        $this->conn = new AMQPConnection(RABBITMQ_HOST, RABBITMQ_PORT, RABBITMQ_USER, RABBITMQ_PASS, RABBITMQ_VHOST);
    }

    public function getChannel()
    {
        return $this->conn->channel();
    }

    public function getSocket()
    {
        return $this->conn->getSocket();
    }

    public function getConn()
    {
        return $this->conn;
    }

    /**
     * @param \PhpAmqpLib\Channel\AMQPChannel $ch
     * @param \PhpAmqpLib\Connection\AbstractConnection $conn
     */
    public function shutdown()
    {
        $this->getChannel()->close();
        $this->getConn()->close();
    }
}