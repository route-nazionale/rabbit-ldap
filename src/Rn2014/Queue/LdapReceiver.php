<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 05:30
 */

namespace Rn2014\Queue;

use PhpAmqpLib\Message\AMQPMessage;
use Rn2014\AESEncoder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface
    ;
class LdapReceiver
{
    protected $app;
    protected $output;
    protected $aesEncoder;

    public static $codificatedFields = [
        'password',
        'old_password'
    ];

    public function __construct(Application $application, OutputInterface $output, AESEncoder $aesEncoder )
    {
        $this->app = $application;
        $this->output = $output;
        $this->aesEncoder = $aesEncoder;
    }

    public function processMessage($req)
    {
        echo "\n--------\n";
        echo var_dump($req->body,true);
        echo "\n--------\n";

        $data = $this->decodeMessage($req->body);

        $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);

        switch ($req->get('routing_key')) {
            case 'humen.insert':
                $stringCommand = 'ldap:user:add';

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'name'    => $data->name,
                    'password'    => $data->password,
                    '--type'    => $data->type,
                );

                break;
            case 'remove_user':

                $stringCommand = 'ldap:user:remove';

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'password'    => $data->password,
                );

                break;
            case 'change_password':

                $stringCommand = 'ldap:change:password';

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'old_password'    => $data->old_password,
                    'password'    => $data->password,
                );

                break;
            case 'remove_group':

                $stringCommand = 'ldap:user:group';

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'group'    => $data->group,
                    '--remove'    => true,
                );

                break;
            case 'add_group':

                $stringCommand = 'ldap:user:group';

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'group'    => $data->group,
                );

                break;
//            case 'test_login':
//                break;
//            case 'login':
//                break;
            default:
                return;
        }

        $command = $this->app->find($stringCommand);
        $input = new ArrayInput($arguments);
        $returnCode = $command->run($input, $this->output);
    }

    public function decodeMessage($message)
    {
        $message = json_decode($message);

        foreach ($message as $field => $value) {
            if (in_array($field, self::$codificatedFields)) {
                $message->$field = $this->aesEncoder->decode($value);
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