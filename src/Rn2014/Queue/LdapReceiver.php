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

    public static $codificatedFields = [
        'password',
        'old_password'
    ];

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

        switch ($message->operation) {
            case 'add_user':
                break;
            case 'change_password':
                break;
            case 'test_login':
                break;
            case 'login':
                break;
            case 'remove_group':
                break;
            case 'add_group':
                break;
            default:
                return;
        }
    }

    public function decodeMessage($message)
    {
        $message = json_decode($message);

        foreach ($message->data as $field => $value) {
            if (in_array(self::$codificatedFields, $field )) {
                $message->data->$field = $this->decode($value);
            }
        }
        return $message;
    }

    protected function decode($value)
    {
        return $value;
    }

    public static function shutdown($channel, $conn)
    {
        $channel->close();
        $conn->close();
    }

}