<?php
/**
 * User: lancio
 * Date: 01/07/14
 * Time: 03:04
 */

namespace Rn2014;

use PhpAmqpLib\Message\AMQPMessage;

class Responder {

    /**
     * @param \PhpAmqpLib\Message\AMQPMessage $msg
     */
    public static function processMessage(AMQPMessage $msg)
    {
        echo "\n--------\n";
        echo $msg->body;
        $data = json_decode(($msg->body));
        var_dump($data);

        echo "\n--------\n";

        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

        if ($msg->body === 'quit') {
            $msg->delivery_info['channel']->basic_cancel($msg->delivery_info['consumer_tag']);
        }
    }

} 