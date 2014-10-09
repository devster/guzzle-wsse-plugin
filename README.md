Guzzle WSSE Plugin
==================

[![Latest Stable Version](https://poser.pugx.org/devster/guzzle-wsse-plugin/v/stable.png)](https://packagist.org/packages/devster/guzzle-wsse-plugin) [![Build Status](https://travis-ci.org/devster/guzzle-wsse-plugin.png?branch=master)](https://travis-ci.org/devster/guzzle-wsse-plugin)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/devster/guzzle-wsse-plugin/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/devster/guzzle-wsse-plugin/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/devster/guzzle-wsse-plugin/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/devster/guzzle-wsse-plugin/?branch=master)

Guzzle Plugin to manage WSSE Authentication

More informations on WSSE authentication [http://www.xml.com/pub/a/2003/12/17/dive.html](http://www.xml.com/pub/a/2003/12/17/dive.html)

* **Guzzle 3: install `1.*` version**
* **Guzzle 4: install `2.*` version**

Installation
------------

### Install via composer

```shell
# Install Composer
curl -sS https://getcomposer.org/installer | php

# Add the plugin as a dependency
php composer.phar require devster/guzzle-wsse-plugin:~2.0
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

Basic usage
-----------

```php
require vendor/autoload.php

use GuzzleHttp\Client;
use Devster\GuzzleHttp\Subscriber\WsseAuth;

// Create a Guzzle client
$client new Client(['base_url' => 'http://example.com']);
// and add it the plugin
(new WsseAuth('username', 'pass****'))->attach($client);
// Or
$client->getEmitter()->attach(new WsseAuth('username', '********'));

// Now the plugin will add the correct WSSE headers to your guzzle request
$response = $client->get('/data')->send();
```

Customization
-------------

You can customize:

* The nonce generation
* The digest generation
* And the date format

```php
use GuzzleHttp\Client;
use GuzzleHttp\Message\RequestInterface;
use Devster\GuzzleHttp\Subscriber\WsseAuth;

$client = new Client;

$plugin = new WsseAuth('username', 'pass****');
$plugin
    ->attach($client)
    ->setNonce(function (RequestInterface $request) {
        return uniqid('my_nonce', true);
    })
    ->setDigest(function ($nonce, $createdAt, $password) {
        return $nonce.$createdAt.$password;
    })
    ->setDateFormat('Y-m-d') // PHP format. Default: c (ISO 8601)
;
```

Tests
-----

    composer install && vendor/bin/phpunit

License
-------

This plugin is licensed under the MIT License
