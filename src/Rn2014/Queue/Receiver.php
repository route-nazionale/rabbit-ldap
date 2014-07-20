<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 05:30
 */

namespace Rn2014\Queue;

use Rn2014\AESEncoder;
use SebastianBergmann\Exporter\Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class Receiver
{
    protected $app;
    protected $output;
    protected $aesEncoder;

    /**
     * @var array campi da decodificare
     */
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
        echo var_dump($req->body, true);
        echo "\n--------\n";

        $data = $this->decodeMessage($req->body);

        $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);

        switch ($req->get('routing_key')) {
            case 'humen.insert':
                $stringCommand = 'ldap:user:add';

                $type = $this->getType($data->fields);

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->fields->cu,
                    'name'    => $data->fields->nome,
                    'password'    => $data->fields->data_nascita,
                    '--type'    => $type,
                );

                break;
            case 'humen.change.pass':

                $stringCommand = 'ldap:change:password';

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'old_password'    => $data->old_password,
                    'password'    => $data->password,
                );

                break;
            case 'humen.reset.pass':

                $stringCommand = 'ldap:reset:password';

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'password'    => $data->password,
                );

                break;
            case 'humen.group.remove':

                $stringCommand = 'ldap:user:group';

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'group'    => $data->group,
                    '--remove'    => true,
                );

                break;
            case 'humen.group.add':

                $stringCommand = 'ldap:user:group';

                $arguments = array(
                    'command' => $stringCommand,
                    'username'    => $data->username,
                    'group'    => $data->group,
                );

                break;
//            case 'humen.remove':
//
//                $stringCommand = 'ldap:user:remove';
//
//                $arguments = array(
//                    'command' => $stringCommand,
//                    'username'    => $data->username,
//                    'password'    => $data->password,
//                );
//
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
            if (in_array(self::$codificatedFields, $field)) {
                $message->$field = $this->aesEncoder->decode($value);
            }
        }
        return $message;
    }

    public static function shutdown($channel, $conn)
    {
        $channel->close();
        $conn->close();
    }


    public function getType($user)
    {
        switch ($user->idgruppo) {
            //extra
            case 'SER0':
            //fornitori
            case 'SER4':
                return 'extra';
                break;
            //lab
            case 'SER1':
                return 'lab';
                break;
            //oneteam
            case 'SER2':
                return 'oneteam';
                break;
        }

        switch ($user->ruolo) {
            //capiclan e annessi
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
                return 'rscapi';
                break;
            //animatori lab
            case '11':
                return 'lab';
                break;
            case '7':
                return 'rs';
                break;
            case '8':
                return 'oneteam';
                break;
            // non definito
            case '99':
            // kinderheim
            case '9':
            // coniugi
            case '10':
            // accompagnatori
            case '12':
            default:
                throw new Exception("tipo utente non riconosciuto [cu: {$user->cu}| ruolo: {$user->ruolo}| idgruppo: {$user->idgruppo}]");
        }
    }
}
