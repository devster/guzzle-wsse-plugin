<?php

namespace Devster\GuzzleHttp\Subscriber;

use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Event\EmitterInterface;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\RequestEvents;
Use GuzzleHttp\Client;

/**
 * WSSE signing plugin
 * @link http://www.xml.com/pub/a/2003/12/17/dive.html
 */
class WsseAuth implements SubscriberInterface
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * Date format - PHP style
     *
     * Default: ISO8601
     *
     * @var string
     */
    protected $dateFormat = 'c';

    /**
     * @var callable
     */
    protected $nonce;

    /**
     * @var callable
     */
    protected $digest;

    /**
     * @var callable
     */
    protected $passwordProcessor;

    /**
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->nonce    = array($this, 'nonce');
        $this->digest   = array($this, 'digest');
    }

    /**
     * Attach the plugin to the client
     *
     * @param Client $client
     *
     * @return WsseAuth The current instance
     */
    public function attach(Client $client)
    {
        $client->getEmitter()->attach($this);

        return $this;
    }

    /**
     * Set dateFormat
     *
     * @param string $dateFormat
     *
     * @return WsseAuth The current instance
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Get dateFormat
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * Set nonce
     *
     * @param callable $nonce
     *
     * @return WsseAuth The current instance
     */
    public function setNonce(callable $nonce)
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * Get nonce
     *
     * @return callable
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Set digest
     *
     * @param callable $digest
     *
     * @return WsseAuth The current instance
     */
    public function setDigest(callable $digest)
    {
        $this->digest = $digest;

        return $this;
    }

    /**
     * Get digest
     *
     * @return callable
     */
    public function getDigest()
    {
        return $this->digest;
    }

    /**
     * Set passwordProcessor
     *
     * @param callable $passwordProcessor
     *
     * @return WsseAuth
     */
    public function setPasswordProcessor($passwordProcessor)
    {
        $this->passwordProcessor = $passwordProcessor;

        return $this;
    }

    /**
     * Get passwordProcessor
     *
     * @return callable
     */
    public function getPasswordProcessor()
    {
        return $this->passwordProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getEvents()
    {
        return array(
            'before' => array('onBefore', RequestEvents::SIGN_REQUEST)
        );
    }

    /**
     * On before event handler
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function onBefore(BeforeEvent $event)
    {
        $now       = new \DateTime;
        $createdAt = $now->format($this->getDateFormat());
        $request   = $event->getRequest();
        $nonce     = call_user_func($this->getNonce(), $request);
        $password  = $this->passwordProcessor ? call_user_func($this->passwordProcessor, $this->password) : $this->password;
        $digest    = call_user_func($this->getDigest(), $nonce, $createdAt, $password);

        $request->setHeader('Authorization', 'WSSE profile="UsernameToken"');
        $request->setHeader(
            'X-WSSE',
            $this->createWsseHeader(
                $this->username,
                $digest,
                $nonce,
                $createdAt
            )
        );
    }

    /**
     * Create the WSSE header
     *
     * @param string $username
     * @param string $nonce
     * @param string $createdAt,
     * @param string $password
     *
     * @return string
     */
    public function createWsseHeader($username, $digest, $nonce, $createdAt)
    {
        return sprintf(
            'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $username,
            $digest,
            base64_encode($nonce),
            $createdAt
        );
    }

    /**
     * Create a password digest
     *
     * @param string $nonce
     * @param string $createdAt
     * @param string $password
     *
     * @return string
     */
    public static function digest($nonce, $createdAt, $password)
    {
        return base64_encode(sha1($nonce.$createdAt.$password, true));
    }

    /**
     * Generate a nonce
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    public static function nonce(RequestInterface $request = null)
    {
        $url = $request ? $request->getUrl() : null;

        return sha1(uniqid('', true) . $url);
    }
}
