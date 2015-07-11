<?php namespace Sidney\Latchet;

use Exception;
use Ratchet\Wamp\WampConnection;

abstract class BaseConnection
{

    abstract function open(WampConnection $connection);


    abstract function close(WampConnection $connection);


    abstract function error(WampConnection $connection, Exception $exception);

}