<?php namespace Sidney\Latchet\Console;

use Event;
use Illuminate\Console\Command;
use Latchet;
use Symfony\Component\Console\Input\InputOption;

class ListenCommand extends Command
{

    /**
     * @var \Sidney\Latchet\Latchet
     */
    protected $latchet;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'latchet:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start listening on specified port for incomming connections';


    /**
     * Create a new command instance.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->latchet = $app->make('latchet');
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $loop = \React\EventLoop\Factory::create();

        if (config('latchet.enablePush')) {
            $this->enablePush($loop);
        }

        // Set up our WebSocket server for clients wanting real-time updates
        $webSock = new \React\Socket\Server($loop);
        $webSock->listen($this->option('port'), '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
        new \Ratchet\Server\IoServer(/**/
            new \Ratchet\Http\HttpServer(/**/
                new \Ratchet\WebSocket\WsServer(/**/
                    new \Ratchet\Wamp\WampServer(/**/
                        $this->latchet))), $webSock);

        if (config('latchet.allowFlash')) {
            $this->allowFlash($loop);
        }

        Event::fire('latchet.start');

        /**
         * heartbeat
         */
        if (config('latchet.enableHeartbeat', false)) {
            $loop->addPeriodicTimer(config('latchet.heartbeatInterval', 5), function () {
                /**
                 * TODO zombie/half connections check goes here
                 */

                /**
                 * send heart to clients
                 */
                Latchet::publish(config('latchet.heartbeatTopic', 'heartbeat'), [ 'heartbeat' ]);

            });
        }

        $this->info('Listening on port ' . $this->option('port') . ' zmq port ' . config('latchet.zmqPort'));
        $loop->run();
    }


    /**
     * Allow Flash sockets to connect to our server.
     * For this we have to listen on port 843 and return
     * the flashpolicy
     *
     * @param \React\EventLoop\StreamSelectLoop $loop
     *
     * @return void
     */
    protected function allowFlash($loop)
    {
        // Allow Flash sockets (Internet Explorer) to connect to our app
        $flashSock = new \React\Socket\Server($loop);
        $flashSock->listen(config('latchet.flashPort'), '0.0.0.0');
        $policy = new \Ratchet\Server\FlashPolicy;
        $policy->addAllowedAccess('*', $this->option('port'));
        $webServer = new \Ratchet\Server\IoServer($policy, $flashSock);

        $this->info('Flash connection allowed');
    }


    /**
     * Enable the option to push messages from
     * the Server to the client
     *
     * @param \React\EventLoop\StreamSelectLoop $loop
     *
     * @return void
     */
    protected function enablePush($loop)
    {
        // Listen for the web server to make a ZeroMQ push after an ajax request
        $context = new \React\ZMQ\Context($loop);
        $pull    = $context->getSocket(\ZMQ::SOCKET_PULL,
            config('latchet.zmqPullId', sprintf('latchet.pull.%s', \App::environment())));
        $pull->bind('tcp://127.0.0.1:' . config('latchet.zmqPort')); // Binding to 127.0.0.1 means the only client that can connect is itself
        $pull->on('message', [ $this->latchet, 'serverPublish' ]);

        $this->info('Push enabled');
    }


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'port',
                'p',
                InputOption::VALUE_OPTIONAL,
                'The Port on which we listen for new connections',
                config('latchet.socketPort')
            ],
        ];
    }

}