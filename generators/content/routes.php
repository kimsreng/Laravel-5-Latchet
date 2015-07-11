Latchet::connection(APP\Latchet\Connection::class);
Latchet::topic('http://api.wamp.ws/procedure#authreq', APP\Latchet\Topics\AuthTopic::class);
Latchet::topic('http://api.wamp.ws/procedure#auth', APP\Latchet\Topics\AuthTopic::class);
Latchet::topic('hello-topic', APP\Latchet\Topics\HelloTopic::class);