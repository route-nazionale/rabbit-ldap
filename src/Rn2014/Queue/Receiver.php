<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 05:30
 */

namespace Rn2014\Queue;

use Rn2014\AESEncoder;
use \Exception;
use Silex\Application;
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
//        echo "\n--------\n";
//        echo var_dump($req->body, true);
//        echo "\n--------\n";
        $this->app['monolog.humen']->addDebug($req->get('routing_key'), ["message" => $req->body]);

        try {
            switch ($req->get('routing_key')) {
                case 'humen.insert':

                    $decodedMessage = $this->decodeMessage($req->body, false);
                    $data = $decodedMessage[0];

                    if (empty($data->fields->cu)) {
                        throw new \Exception("cu non trovato");
                    }
                    if (empty($data->fields->data_nascita)) {
                        $data->fields->data_nascita = substr(md5(uniqid()), 0, 10);
                    }

                    $type = $this->getType($data->fields);
                    $this->app['ldap.admin']->setPathScripts(LDAP_PATH_SCRIPTS);

                    $result =  $this->app['ldap.admin']->addUser(
                        $type,
                        $data->fields->cu,
                        $data->fields->nome . ' ' . $data->fields->cognome,
                        $data->fields->data_nascita
                    );

                    $this->app['monolog.humen']->addNotice("user inserted", [
                        'routing_key' => $req->get('routing_key'),
                        'data' => $data,
                        'result' => $result,
                    ]);

                    break;
                case 'humen.password':

                    $data = $this->decodeMessage($req->body);

                    if (empty($data->username) || empty($data->password)) {
                        throw new \Exception("cu o pass non trovata");
                    }

                    $this->app['ldap.admin']->setPathScripts(LDAP_PATH_SCRIPTS);

                    $result = $this->app['ldap.admin']->resetPassword($data->username, $data->password);
                    $this->app['monolog.humen']->addNotice("reset password", [
                            'routing_key' => $req->get('routing_key'),
                            'data' => $data,
                            'result' => $result,
                        ]);

                    break;
                case 'humen.groups':

                    $data = $this->decodeMessage($req->body, false);

                    if (empty($data->username) ||
                            !is_array($data->add) ||
                            !is_array($data->remove)) {
                        throw new \Exception("username o gruppi add/remove non corretti");
                    }

                    foreach ($data->add as $group) {

                        $result = $this->app['ldap.admin']->userChangeGroup($data->username, $group, true);

                        $this->app['monolog.humen']->addNotice("group added", [
                                'routing_key' => $req->get('routing_key'),
                                'data' => $data,
                                'result' => $result,
                            ]);
                    }

                    foreach ($data->remove as $group) {
                        $result = $this->app['ldap.admin']->userChangeGroup($data->username, $group, false);

                        $this->app['monolog.humen']->addNotice("group subbed", [
                                'routing_key' => $req->get('routing_key'),
                                'data' => $data,
                                'result' => $result,
                            ]);
                    }

                    break;
                //            case 'humen.group.remove':
                //
                //                $stringCommand = 'ldap:user:group';
                //
                //                $arguments = array(
                //                    'command' => $stringCommand,
                //                    'username'    => $data->username,
                //                    'group'    => $data->group,
                //                    '--remove'    => true,
                //                );
                //
                //                break;
                //            case 'humen.group.add':
                //
                //                $stringCommand = 'ldap:user:group';
                //
                //                $arguments = array(
                //                    'command' => $stringCommand,
                //                    'username'    => $data->username,
                //                    'group'    => $data->group,
                //                );
                //
                //                break;
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
                //            case 'humen.change.pass':
                //
                //                $stringCommand = 'ldap:change:password';
                //
                //                $arguments = array(
                //                    'command' => $stringCommand,
                //                    'username'    => $data->username,
                //                    'old_password'    => $data->old_password,
                //                    'password'    => $data->password,
                //                );
                //
                //                break;

                default:

                    $req->delivery_info['channel']->basic_nack($req->delivery_info['delivery_tag']);
                    $this->app['monolog.humen']->addError('routing_key non riconosciuta', [
                        'routing_key' => $req->get('routing_key'),
                        'message' => $req->body,
                    ]);
                    return;
            }

            $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);

        } catch (\Exception $e) {
            $req->delivery_info['channel']->basic_nack($req->delivery_info['delivery_tag']);
            $this->app['monolog.humen']->addError($e->getMessage(), [
                'routing_key' => $req->get('routing_key'),
                'message' => $req->body,
            ]);
        }
    }

    public function decodeMessage($message, $decrypt = true)
    {
        $message = json_decode($message);

        if (!$decrypt ) {
            return $message;
        }
        foreach ($message as $field => $value) {
            if (in_array($field, self::$codificatedFields)) {
                $message->$field = $this->aesEncoder->decode($value);
            }
        }
        return $message;
    }

    public function shutdown($channel, $conn)
    {
        $this->app['monolog.humen']->addEmergency("shutdown", []);

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
