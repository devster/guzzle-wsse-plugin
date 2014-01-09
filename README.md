Guzzle WSSE Plugin
==================

[![Latest Stable Version](https://poser.pugx.org/devster/guzzle-wsse-plugin/v/stable.png)](https://packagist.org/packages/devster/guzzle-wsse-plugin) [![Build Status](https://travis-ci.org/devster/guzzle-wsse-plugin.png?branch=master)](https://travis-ci.org/devster/guzzle-wsse-plugin)

Plugin Guzzle to manage WSSE Authentication

More informations on WSSE authentication [http://www.xml.com/pub/a/2003/12/17/dive.html](http://www.xml.com/pub/a/2003/12/17/dive.html)

Installation
------------

### Install via composer

```shell
# Install Composer
curl -sS https://getcomposer.org/installer | php

# Add the plugin as a dependency
php composer.phar require devster/guzzle-wsse-plugin:~1.0
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

Basic usage
-----------

```php
require vendor/autoload.php

use Guzzle\Http\Client;
use Devster\Guzzle\Plugin\Wsse\WssePlugin;

// Create a Guzzle client
$client new Client('http://example.com');
// and add it the plugin
$client->addSubscriber(new WssePlugin(array(
    'username'  => 'rupert',
    'password' => '*********'
)));
// Now the plugin will add the correct WSSE headers to your guzzle request

$response = $client->get('/data')->send();
```

Customization
-------------

### Customize the way the nonce is created

The nonces created by the plugin doesn't fit your need? you think their not generated in secure way?

All right you can change that:

```php
use Guzzle\Common\Event;
use Guzzle\Http\Client;
use Devster\Guzzle\Plugin\Wsse\WssePlugin;

$plugin = new WssePlugin(array(
    'username' => 'rupert',
    'password' => '*******',
    'nonce_callback' => function (Event $event) {
        return uniqid('myapp_', true);
    }
));

// and we add the plugin to the client of course
$client = new Client('http://example.com');
$client->addSubscriber($plugin);
```

### Customize the way the timestamp is created

The timestamp created by the plugin doesn't fit your need?

```php
use Guzzle\Common\Event;
use Guzzle\Http\Client;
use Devster\Guzzle\Plugin\Wsse\WssePlugin;

$plugin = new WssePlugin(array(
    'username' => 'rupert',
    'password' => '*******',
    // /!\ The timestamp callback must return a \DateTime instance
    'timestamp_callback' => function (Event $event) {
        $date = new \DateTime();
        return $date;
    }
));

// and we add the plugin to the client of course
$client = new Client('http://example.com');
$client->addSubscriber($plugin);
```

License
-------

This plugin is licensed under the MIT License