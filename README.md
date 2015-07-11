# Latchet (Laravel 5 Package, WAMP@v1)

##Important

This is not even an alpha version. There's still a lot of stuff going on. The docs aren't finished, and some of the code needs to get polished. So please don't use the package for production - yet. If you want to keep up to date you can follow me on [Twitter](https://twitter.com/sidneywidmer "Twitter")

##Intro

Latchet takes the hassle out of PHP backed realtime apps. At its base, it's a extended version of  [Ratchet](https://github.com/cboden/Ratchet "Ratchet") to work nicely with laravel.

If you're finished setting up a basic WampServer, you'll have something like this:
```
Latchet::topic('chat/room/{roomid}', APP\Latchet\Topics\ChatRoomTopic::class);
```

## Installation

### Earlybird

Until i submit the pakage to packagist, include it directly from github.

```json
"repositories": [
	{
		"type": "vcs",
		"url": "https://github.com/lokielse/Larvel-5-Latchet"
	}
],
"require": {
	"sidney/latchet": "2.*"
}
```

### Required setup

In the `require` key of `composer.json` file add the following

```
"sidney/latchet": "2.*"
```

Run the Composer update comand
```bash
$ composer update
```

In your `config/app.php` add `'Sidney\Latchet\LatchetServiceProvider::class'` to the end of the `$providers` array

```
Sidney\Latchet\LatchetServiceProvider::class
```

At the end of `config/app.php` add `'Latchet'    => Sidney\Latchet\LatchetFacade::class` to the `$aliases` array

```
'Latchet'    => Sidney\Latchet\LatchetFacade::class
```

### Configuration

Publish the config file with the following artisan command: `php artisan vendor:publish`

There are not a lot of configuration options, the most important will be `enablePush` and `zmqPort`. This requires some extra configuration on your server and is discussed in the next section.

Your can config latchet in `.env` file
```
LATCHET_SOCKET_PORT=1111
LATCHET_ENABLE_PUSH=true
LATCHET_ZMQ_PORT=5555
...
```

The rest of the options should be pretty self self-explanatory.

### Enable push

Todo, until then -> Braindump:

* Installing zermq; (in this order) ubuntu
* http://johanharjono.com/archives/633 (if this doesn't work, compile from source)
* http://www.zeromq.org/intro:get-the-software
* http://www.zeromq.org/bindings:php (eventualli apt-get install pkg-config, make)
* add extension=zmq.so to php.ini
* check if extension loaded php -m
* check if zeromq package (zlib1g) is installed dpkg --get-selections

### Deploy
see [Documentation](http://socketo.me/docs/deploy) here

## Usage

### Introduction

Like mentioned before, Latchet is based on Ratchet and just extends its functionality with some nice extra features like passing parameters in ***topics***. But Latchet also removes some of the flexibility Ratchet provides for the sake of simplicity. Latchet solely focuses on providing a WampServer for your aplications.

#### Topics

I would really recommend you to read through the [Ratchet docs](http://socketo.me/docs/ "Ratchet docs"). They explain the basic principles very clearly.

Once you get the hang of it, topics are really easy to understand. Imagine a standart laravel route as you know it.
```php
Latchet::topic('http://api.wamp.ws/procedure#authreq', APP\Latchet\Topics\AuthTopic::class);
Latchet::topic('http://api.wamp.ws/procedure#auth', APP\Latchet\Topics\AuthTopic::class);
Latchet::topic('hello-topic', APP\Latchet\Topics\HelloTopic::class);
```

Topics (or if you are familiar with other forms of messaging, channels) are the same for websocket connections.
There's always a client which subscribes to a topic. If other clients connect to the same topic, they can then broadcast messages to this subscribed topic or a specific client connected to this topic. See how to register a Controller which handles incomming connections in the next chapter.

### Server

Everything we're doing in this section is just to set up a WampServer which will then be started from the command line and listen on incomming connections. Basically there are two different handlers we have to set up. One which handles different connection actions and (at least) one for our topic(s) actions.

To clarify stuff:

***Connection actions are:***

* open
* close
* error

***Topic actions are:***

* subscribe
* publish
* call
* unsubscribe

#### Generate files - the artisan way

To simplify the process, there's an easy to use artisan command to generate all the necessary files:

```bash
$ php artisan latchet:generate
```


This will do two things. First it'll create the folder app/socket and copy two files in this folder.

```
App/Http/routes.php
App/Latchet/Topics/AuthTopic.php
App/Latchet/Topics/HeartbeatTopic.php
App/Latchet/Topics/HelloTopic.php
App/Latchet/Connection.php
```



Basically you could now start the server and subscribe to `hello-topic`. I'd recommend to check the next two chapters as they explain what you can do with the newly added connection and topic handlers.

#### Connection handler

If you've ran the above `artisan:generate` command, you'll have a connection handler registered in your routes.php file. It defines how to react on different connection actions. So anytime a new connection to the server is establish, we'll ask the controller what to do. Easy as a pie:

```php
Latchet::connection(APP\Latchet\Connection::class);
```

It handles the following actions:

* open
* close
* error

All three actions get a `Connection` object as `$connection`. Read more about this objectin the official Ratchet api documentation:[Ratchet API - Class WampConnection](http://socketo.me/api/class-Ratchet.Wamp.WampConnection.html "Ratchet API")

For example, you could here close a connection `$connection->close()`, or add some additional info to the connection object:

```php
$connection->Chat        = new \StdClass;
$connection->Chat->rooms = array();
$connection->Chat->name  = $connection->WAMP->sessionId;
```

From now on, `$connection->Chat->name` will always be available in the `$connection` variable which gets passed to most of the action methods.

Because the server should be constantly running, there's an extra function for error handling. Whenever an error occurs, the error function is triggered. In the default template, which gets generated by the artisan command, the error just gets thrown again. This stops the server and the error is displayed in your console. For production it's important that you don't rethrow the error, but instead log it. An error gets thrown if someone for example tries to connect to a non existend topic.

#### Topic handlers

Now it gets interesting. With latchet you can register new topics and pass parameters to it:

```php
Latchet::topic('chat/room/{roomId}', APP\Latchet\Topics\ChatRoomTopic::class);
```

And in the topic handler (e.g. `app/Latchet/Topics/ChatRoomTopic.php`):

```php
<?php
namespace APP\Latchet\Topics;

use \Sidney\Latchet\BaseTopic;

class ChatRoomTopic extends BaseTopic {

public function subscribe($connection, $topic, $roomId = null)
{
	//useful for debuging as this will echo the text in the console
	echo $roomId;
}
```

If a client now subscribes to `chat/room/lobby` you get the value 'lobby' in your class.

And if you want to broadcast a message (gets json encoded) to all other subscribers of a particular channel:
```
public function publish($connection, $topic, $message, array $exclude, array $eligible)
{
	$this->broadcast($topic, ['msg' => 'New broadcasted message!'])
}
```

There are other methods to handle the following actions:

* subscribe
* publish
* call
* unsubscribe

#### Push

If you have push enabled in your config file, it's also possible to publish messages from different locations in your application.

```php
Latchet::publish('chat/room/lobby', ['msg' => 'foo']);
```

Like that you could for example react to ajax requests.


#### Start the server

Use the following artisan command to start the server:
```bash
$ sudo php artisan latchet:listen
```

Also make shure to read the Ratchet docs on how to deploy your app: [Ratchet Docs - Deployment](http://socketo.me/docs/deploy "Ratchet Docs")

One word to the environment: Because the whole application will be running from the console, make shure to pass the desired environment as a parameter in your console command e.g:

```bash
$ sudo php artisan latchet:listen --env=local
```


### Client

Now that we have our server up and running, we somehow need to connect to it right? [Autobahn JS](http://autobahn.ws/js "Autobahn JS") to the rescue.

#### Javascript / Legacy browsers

[Autobahn JS](http://autobahn.ws/js "Autobahn JS") handles the client side for us. Make shure to check their docs, in the meantime, here's a basic example:

```javascript
	conn = new ab.Session(
		'ws://latchet.laravel-devbox.dev:1111', // The host (our Latchet WebSocket server) to connect to
		function() { // Once the connection has been established
			conn.subscribe('chat/room/lobby', function(topic, event) {
				console.log('event: ');
				console.log(event);
			});
		},
		function() {
			// When the connection is closed
			console.log('WebSocket connection closed');
		},
		{
			// Additional parameters, we're ignoring the WAMP sub-protocol for older browsers
			'skipSubprotocolCheck': true
		}
	);
```

For older browsers, which do not support websockts, make shure to inlcude [web-socket-js](https://github.com/gimite/web-socket-js "web-socket-js") and allow flash in your config file.

#### Demoapp

Check the demo application built with laravel, the latchet package, autobahn.js and backbone: [whatup](https://github.com/sidneywidmer/whatup "whatup")
And for a live demo: [whatup.im](http://whatup.im "whatup.im")
