<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 05:30
 */

    namespace Rn2014\Queue;


class LdapReceiver
{
    public static $stopWord = "quit";

    public function processMessage($msg)
    {
        echo "\n--------\n";
        //print_r($msg);
        echo $msg->body;
        echo "\n--------\n";


        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

        $message = $this->decodeMessage($msg->body);

        // Send a message with the string "quit" to cancel the consumer.
        if ($message === LdapReceiver::$stopWord) {
            $msg->delivery_info['channel']->basic_cancel($msg->delivery_info['consumer_tag']);
            return;
        }

        switch ($message) {

        }
    }

    public function decodeMessage($message)
    {

        return $message;
    }

    public static function shutdown($channel, $conn)
    {
        $channel->close();
        $conn->close();
    }

}