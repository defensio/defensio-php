<?php
require_once 'PHPUnit/Framework.php';
require_once 'defensio-php/Defensio.php';

define(MOCK_RESTCLIENT, TRUE);

class phpDefensioTest extends PHPUnit_Framework_TestCase
{

    public function setup()
    {
        $this->api_key = 'key';
        $this->mock_rest_client = MOCK_RESTCLIENT;
    }

    public function testDefensioConstructor()
    {

        $d = new Defensio($this->api_key, 'Test');
        $this->assertEquals($this->api_key, $d->getApiKey());
        $this->assertNull($d->authenticated);
    }

    public function testGetUserInvalid()
    {
        $d = new Defensio($this->api_key . "__not_valid");

        if($this->mock_rest_client) {
            $result = "
                <defensio-result>
                    <owner-url>http://key.com</owner-url>
                    <api-version>2.0</api-version>
                    <status>fail</status>
                    <message>API key not found</message>
                </defensio-result>";
            $client = $this->getMock('DefensioRESTClient', array('get'));
            $client->expects($this->once())->method('get')->will($this->returnValue(array(404, $result, NULL )));
            $d->rest_client = $client;
        }

        $res = $d->getUser();
        $this->assertTrue($res[0] == 404);
    }

    public function testIsKeyValidWhenValid()
    {
        $d = new Defensio($this->api_key, 'Test');
        if($this->mock_rest_client) {
            $client = $this->getMock('DefensioRESTClient', array('get'));
            $result = "
                <defensio-result>
                    <owner-url>http://key.com</owner-url>
                    <api-version>2.0</api-version>
                    <status>success</status>
                    <message></message>
                </defensio-result>";
            $client->expects($this->once())->method('get')->will($this->returnValue(array(200, $result, array() )));
            $d->rest_client = $client;
        }
        $res = $d->getUser();
        $this->assertTrue($res[0] == 200);
    }

    public function testAnalizeDocumentWhenUnauthorized()
    {
        $d = new Defensio($this->api_key . '__', 'Test');

        if($this->mock_rest_client) {
            $client = $this->getMock('DefensioRESTClient', array('post'));
            $result = "
                <defensio-result>
                    <owner-url>http://key.com</owner-url>
                    <api-version>2.0</api-version>
                    <status>fail</status>
                    <message>API key not found</message>
                </defensio-result>";
            $client->expects($this->once())->method('post')->will($this->returnValue(array(401, $result, array() )));
            $d->rest_client = $client;
        }

        try {
            ($d->postDocument(Array('author_name' => 'Awesome Guy', 'content' => 'foo bar', 'type' => 'comment', 'platform' => 'PHP_app')));
        }
        catch(DefensioInvalidKey $expected) {
            return;
        }

        $this->fail('Expected Defensio exception not thrown');
    }

    public function testAnalizeDocumentWhenInvalid()
    {
        $d = new Defensio($this->api_key, 'Test');

        if($this->mock_rest_client) {
            $client = $this->getMock('DefensioRESTClient', array('post'));
            $result = <<<XML
<defensio-result>
  <api-version type="float">2.0</api-version>
  <message>The following fields are missing but required: content, type</message>
  <status>fail</status>
</defensio-result>

XML;
            $client->expects($this->once())->method('post')->will($this->returnValue(array(200, $result, array() )));
            $d->rest_client = $client;
        }

        try {
            ($d->postDocument(Array('author_name' => 'Awesome Guy')));
        }
        catch(DefensioFail $expected) {
            return;
        }

        $this->fail('Expected Defensio exception not thrown');
    }

    public function testAnalizeDocumentWhenAsyncOK()
    {
        $d = new Defensio($this->api_key, 'Test');
        if($this->mock_rest_client) {
            $client = $this->getMock('DefensioRESTClient', array('post'));
            $result = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<defensio-result>
  <allow></allow>
  <signature>300f959c9f8430fd26ee6f</signature>
  <spaminess nil="true"></spaminess>
  <dictionary-match></dictionary-match>
  <api-version>2.0</api-version>
  <classification></classification>
  <message></message>
  <status>pending</status>
</defensio-result>

XML;
            $client->expects($this->once())->method('post')->will($this->returnValue(array(200, $result, array() )));
            $d->rest_client = $client;
        }
        $result = $d->postDocument(Array('author_name' => 'Jhon Doe', 'content' => 'Lorem ipsum... et cetera', 'type' => 'comment', 'platform' => 'PHP_app', 'async' => 'true'));
        $this->assertTrue(is_object($result[1]));
        $result_obj = $result[1];

        $this->assertTrue( empty($result_obj->classification) ) ;
        $this->assertTrue( empty($result_obj->allow) );
        $this->assertObjectHasAttribute('signature', $result_obj);
        $this->assertTrue($result_obj->status == 'pending');
    }

    public function testAnalizeDocumentWhenSyncOK()
    {
        $d = new Defensio($this->api_key, 'Test');

        if($this->mock_rest_client) {
            $client = $this->getMock('DefensioRESTClient', array('post'));
            $result = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<defensio-result>
  <allow type="boolean">true</allow>
  <signature>832c8d2ff6cd15807d0f36</signature>
  <spaminess type="float">0.05</spaminess>
  <dictionary-match type="boolean">false</dictionary-match>
  <api-version>2.0</api-version>
  <classification>legitimate</classification>
  <message></message>
  <status>success</status>
</defensio-result>

XML;
            $client->expects($this->once())->method('post')->will($this->returnValue(array(200, $result, array() )));
            $d->rest_client = $client;
        }

        $result = $d->postDocument(Array('author_name' => 'Jhon Doe', 'content' => 'Lorem ipsum... et cetera', 'type' => 'comment', 'platform' => 'PHP_app'));
        $this->assertTrue(is_object($result[1]));
        $result_obj = $result[1];

        $this->assertTrue( 'legitimate' == $result_obj->classification ) ;
        $this->assertTrue( 'true' == $result_obj->allow );
        $this->assertObjectHasAttribute('signature', $result_obj);
        $this->assertTrue($result_obj->status == 'success');
    }

    public function testPublishDocumentWhenOK()
    {
        $d = new Defensio($this->api_key, 'Test');
        if($this->mock_rest_client) {
            $client = $this->getMock('DefensioRESTClient', array('post'));
            $result = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<defensio-result>
  <allow></allow>
  <signature>d6e9470bac6909990edadb</signature>
  <spaminess nil="true"></spaminess>
  <dictionary-match></dictionary-match>
  <api-version>2.0</api-version>
  <classification></classification>
  <message></message>
  <status>pending</status>
</defensio-result>
XML;
            $client->expects($this->once())->method('post')->will($this->returnValue(array(200, $result, array() )));
            $d->rest_client = $client;
        }


        $result = $d->postDocument(Array('author_name' => 'Jhon Doe', 'content' => 'Lorem ipsum... et cetera', 'type' => 'article', 'platform' => 'PHP_app'));
        $this->assertTrue(is_object($result[1]));
        $result_obj = $result[1];

        $this->assertObjectHasAttribute('signature', $result_obj);
    }


    public function testReportDocumentAsLegitimateWhenOk()
    {
        if($this->mock_rest_client) return;

        $d = new Defensio($this->api_key, 'Test');
        $result = $d->postDocument(Array('type' => 'comment', 'author_name' => 'Jhon Doe', 'content' => 'Lorem ipsum... et cetera', 'platform' => 'PHP_app'));
        $result_obj = $result[1];
        $signature = $result_obj->signature;
        $d->putDocument($signature, array('allow' => 'true'));
    }

    public function testReportDocumentAsLegitimateWhenDocumentNotFound()
    {
        if ($this->mock_rest_client) return;
        $d = new Defensio($this->api_key, 'Test');
        $signature = 'random_not_existent';
        try{
            $result = $d->putDocument($signature, array('allow' => 'true'));
        } catch( DefensioUnexpectedHTTPStatus $ex) {
            return;
        } 
        $this->fail('Expected Defensio exception not thrown');
    }

    public function testGetDocumentWhenInexistent()
    {
        $d = new Defensio($this->api_key, 'Test');

        if($this->mock_rest_client) {
            $client = $this->getMock('DefensioRESTClient', array('get'));
            $result = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<defensio-result>
  <api-version>2.0</api-version>
  <message>Document with signature: random_crap_here not found</message>
  <status>fail</status>
</defensio-result>
XML;
            $client->expects($this->once())->method('get')->will($this->returnValue(array(404, $result, array() )));
            $d->rest_client = $client;
        }

        try{
            $result = $d->getDocument('random_crap_here');
        }catch(DefensioFail $expected){
            $this->assertEquals(404, $expected->http_status);
            return;
        }

        $this->fail('Expecting DefensioFail to be trhown');
    }

    public function testGetDocumentWhenExistent()
    {
        $d = new Defensio($this->api_key, 'Test');
        if($this->mock_rest_client) {
            $client = $this->getMock('DefensioRESTClient', array('get'));
            $result = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<defensio-result>
  <api-version>2.0</api-version>
  <message></message>
  <allow>false</allow>
  <signature>spamsignature1</signature>
  <status>success</status>
  <profanity-match>false</profanity-match>
</defensio-result>
XML;
            $client->expects($this->once())->method('get')->will($this->returnValue(array(200, $result, array() )));
            $d->rest_client = $client;
        }
        $result = $d->getDocument('spamsignature1');

        $this->assertTrue($result[1]->status == 'success');
        $this->assertTrue($result[1]->allow == 'false');
        $this->assertTrue($result[1]->{'profanity-match'} == 'false');
        $this->assertEquals(200, $result[0]);

    }

}
?>
