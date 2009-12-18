<?php
require_once 'PHPUnit/Framework.php';
require_once 'phpDefensio/phpDefensio.php';

class Defensio_REST_Client_Test extends PHPUnit_Framework_TestCase
{
    public function test_constructor()
    {
        $client = new Defensio_REST_Client('http://api.defensio.com/');
        $this->assertEquals('http://api.defensio.com/', $client->base_url);
        $this->assertFalse($client->use_sockets);
    }

    public function test_get()
    {
        $client = new Defensio_REST_Client();
        $result = $client->get('/');
        $this->assertType('array', $result);
        $this->assertEquals(302, $result[0]);
        $this->assertType('array', $result[2]);
    }

    public function test_post()
    {
        $client = new Defensio_REST_Client();
        $result = $client->post('/', Array('foo' => 'bar', 'fooz' => 'barz'));
        $this->assertType('array', $result);
        $this->assertEquals(302, $result[0]);
        $this->assertType('array', $result[2]);
    }

}

?>
