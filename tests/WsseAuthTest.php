<?php

namespace Devster\Test\GuzzleHttp\Subscriber;

use Devster\GuzzleHttp\Subscriber\WsseAuth;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Mock;

/**
 * @covers Devster\GuzzleHttp\Subscriber\WsseAuth
 */
class WsseAuthTest extends \PHPUnit_Framework_TestCase
{
    protected function createPlugin()
    {
        return new WsseAuth('John', 'pass');
    }

    protected function createClient()
    {
        $client = new Client();

        $mock = new Mock(array(new Response(200)));
        $client->getEmitter()->attach($mock);

        return $client;
    }

    public function testNonce()
    {
        $request = $this->createClient()->createRequest('GET', 'http://example.com');

        $nonces = array();

        for ($i = 0; $i < 10000; $i++) {
            $nonces[] = WsseAuth::nonce($request);
        }

        $this->assertEquals(10000, count(array_unique($nonces)));
    }

    public function testDigest()
    {
        $this->assertEquals(
            base64_encode(sha1('nonce'.'timestamp'.'password', true)),
            WsseAuth::digest('nonce', 'timestamp','password')
        );
    }

    protected function wsseHeaderTest($header, array $expected)
    {
        $this->assertNotNull($header);
        $this->assertEquals(1, preg_match(
                '/UsernameToken Username="([^"]+)", PasswordDigest="([^"]+)", Nonce="([^"]+)", Created="([^"]+)"/',
                $header,
                $matches
            )
        );

        foreach ($expected as $key => $val) {
            $this->assertEquals($val, $matches[$key+1]);
        }
    }

    public function testCreateWsseHeader()
    {
        $p = $this->createPlugin();

        $header = $p->createWsseHeader('John', 'digest', 'nonce', 'createdAt');

        $this->wsseHeaderTest($header, array(
            'John',
            'digest',
            base64_encode('nonce'),
            'createdAt'
        ));
    }

    public function testFunctional()
    {
        $client = $this->createClient();
        $plugin = $this->createPlugin();
        $client->getEmitter()->attach($plugin);

        $request = $client->createRequest('GET', 'http://example.com');

        $this->assertEmpty($request->getHeader('Authorization'));
        $this->assertEmpty($request->getHeader('X-WSSE'));

        $client->send($request);

        // test the Authorization header
        $this->assertNotNull($header = $request->getHeader('Authorization'));
        $this->assertEquals($header, 'WSSE profile="UsernameToken"');

        // test the x-wsse header
        $this->assertNotNull($header = $request->getHeader('X-WSSE'));
        $this->assertEquals(1, preg_match(
                '/UsernameToken Username="([^"]+)", PasswordDigest="([^"]+)", Nonce="([^"]+)", Created="([^"]+)"/',
                $header,
                $matches
            )
        );
        $this->assertEquals('John', $matches[1]);
        $this->assertEquals(WsseAuth::digest(base64_decode($matches[3]), $matches[4], 'pass'), $matches[2]);
        $this->assertInternalType('string', $matches[3]);
        $this->assertRegExp('/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})/', $matches[4]);
    }

    public function testCustom()
    {
        $client = $this->createClient();
        $plugin = $this->createPlugin();
        $plugin
            ->attach($client)
            ->setDigest(function ($nonce, $createdAt, $password) {
                return 'custom_digest+'.$password;
            })
            ->setNonce(function () {
                return 'custom_nonce';
            })
            ->setDateFormat('m-Y')
            ->setPasswordProcessor(function ($password) {
                return 'custom_'.$password;
            })
        ;

        $request = $client->createRequest('GET', 'http://example.com');
        $client->send($request);

        $this->wsseHeaderTest($request->getHeader('X-WSSE'), array(
            'John',
            'custom_digest+custom_pass',
            base64_encode('custom_nonce'),
            date('m-Y')
        ));
    }
}
