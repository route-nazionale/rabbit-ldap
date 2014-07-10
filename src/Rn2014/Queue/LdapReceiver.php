<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 05:30
 */

namespace Rn2014\Queue;

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

    public static $codificatedFields = [
        'password',
        'old_password'
    ];

    public function __construct(Application $application, OutputInterface $output )
    {
        $this->app = $application;
        $this->output = $output;
    }

    public function processMessage($req)
    {
        echo "\n--------\n";
        echo $req->body;
        echo "\n--------\n";


        $message = $this->decodeMessage($req->body);

        $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);

        $data = $message->data;

        switch ($message->operation) {
            case 'add_user':
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

                $data = $message->data;

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'password'    => $data->password,
                );

                break;
            case 'change_password':

                $stringCommand = 'ldap:change:password';

                $data = $message->data;

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'old_password'    => $data->old_password,
                    'password'    => $data->password,
                );

                break;
            case 'remove_group':

                $stringCommand = 'ldap:user:group';

                $data = $message->data;

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'group'    => $data->group,
                    '--remove'    => true,
                );

                break;
            case 'add_group':

                $stringCommand = 'ldap:user:group';

                $data = $message->data;

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

        foreach ($message->data as $field => $value) {
            if (in_array($field, self::$codificatedFields)) {
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