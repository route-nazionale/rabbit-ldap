<?php
/**
 * User: lancio
 * Date: 20/07/14
 * Time: 15:39
 */

namespace unit\Rn2014\Queue;

use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Rn2014\Queue\Receiver;
use Symfony\Component\Console\Output\NullOutput;

class ReceiverTest extends \PHPUnit_Framework_TestCase {

    public function setUp()
    {
        $this->app = $this->getMockBuilder('Symfony\Component\Console\Application')
            ->disableOriginalConstructor()
            ->getMock();

        $this->outputNull = new NullOutput();

        $this->encoder = $this->getMockBuilder('Rn2014\AESEncoder')
            ->disableOriginalConstructor()
            ->getMock();


        $this->receiver = new Receiver($this->app, $this->outputNull, $this->encoder);
    }

    public function testUserTypeByIdgroup()
    {
        $user = new \stdClass();
        $user->cu = "test";
        $user->ruolo = "XX";
        $user->idgruppo = "SER0";

        $type = $this->receiver->getType($user);
        $this->assertEquals('extra', $type);

        $user->cu = "test";
        $user->ruolo = "xx";
        $user->idgruppo = "SER1";

        $type = $this->receiver->getType($user);
        $this->assertEquals('lab', $type);

        $user->cu = "test";
        $user->ruolo = "xx";
        $user->idgruppo = "SER2";

        $type = $this->receiver->getType($user);
        $this->assertEquals('oneteam', $type);

        $user->cu = "test";
        $user->ruolo = "xx";
        $user->idgruppo = "SER4";

        $type = $this->receiver->getType($user);

        $this->assertEquals('extra', $type);
    }

    public function testUserTypeByRole()
    {
        $user = new \stdClass();

        $user->cu = "test";
        $user->idgruppo = "XXX";
        for ($role = 0; $role < 7; $role ++) {
            $user->ruolo = "0";

            $type = $this->receiver->getType($user);
            $this->assertEquals('rscapi', $type);
        }

        $user->cu = "test";
        $user->ruolo = "7";
        $user->idgruppo = "XXX";

        $type = $this->receiver->getType($user);
        $this->assertEquals('rs', $type);

        $user->cu = "test";
        $user->ruolo = "8";
        $user->idgruppo = "XXX";

        $type = $this->receiver->getType($user);
        $this->assertEquals('oneteam', $type);
    }

    /**
     * @expectedException \Exception
     */
    public function testKinderheimUserNotInLdap()
    {
        $user = new \stdClass();

        $user->cu = "test";
        $user->idgruppo = "XXX";
        $user->ruolo = "9";

        $type = $this->receiver->getType($user);
    }

    /**
     * @expectedException \Exception
     */
    public function testConiugeKinderheimUserNotInLdap()
    {
        $user = new \stdClass();

        $user->cu = "test";
        $user->idgruppo = "XXX";
        $user->ruolo = "10";

        $type = $this->receiver->getType($user);
    }

    /**
     * @expectedException \Exception
     */
    public function testAccompagnatoreUserNotInLdap()
    {
        $user = new \stdClass();

        $user->cu = "test";
        $user->idgruppo = "XXX";
        $user->ruolo = "12";

        $type = $this->receiver->getType($user);
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidUser()
    {
        $user = new \stdClass();
        $user->cu = "test";
        $user->ruolo = "xx";
        $user->idgruppo = "XX";

        $type = $this->receiver->getType($user);
    }
}
 