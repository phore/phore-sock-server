<?php
/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 18.02.17
 * Time: 20:21
 */

namespace Phore\SockServer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SocketServer
{

    private $mSock = false;

    /**
     * @var SocketServerProcessor[]
     */
    private $mProcessors = [];

    /**
     * @var LoggerInterface
     */
    private $log;


    public function __construct(string $listenAddr = null, int $port=62111, LoggerInterface $logger=null)
    {
        if ($listenAddr == null) {
            $listenAddr = "0.0.0.0";
        }
        if ($logger === null)
            $logger = new NullLogger();
        $this->log = $logger;

        $this->log->notice("Listening on $listenAddr:$port");

        if( ! ($sock = socket_create(AF_INET, SOCK_DGRAM, 0))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);

            throw new \Exception("Cannot create socket $listenAddr:$port : $errormsg ($errorcode)");
        }

        if( !socket_bind($sock, $listenAddr , $port) ) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);

            throw new \Exception("Could not bind socket  $listenAddr:$port : [$errorcode] $errormsg");
        }
        $this->mSock = $sock;
    }

    public function addProcessor (SocketServerProcessor $processor) {
        $this->mProcessors[] = $processor;
    }

    /**
     *
     * @return SocketServerProcessor[]
     */
    public function getProcessors () {
        return array_values($this->mProcessors);
    }


    private function _processMessage ($message, $remoteIp, $remotePort) {
        $this->log->debug("New Message from $remoteIp: $message");
        $accepted = false;
        foreach ($this->mProcessors as $processor) {
            if ($processor->injectStringMessage($remoteIp, $remotePort, $message))
                $accepted = true;
        }

        if ( ! $accepted)
            $this->log->debug("Received garbage from $remoteIp: $message");
        return false;
    }


    public function run($flushInterval = 5) {
        $lastFlush = time();
        $pid = false;
        while(1)
        {
            //Receive some data
            $recLen = socket_recvfrom($this->mSock, $buf, 8024, MSG_DONTWAIT, $remote_ip, $remote_port);
            if ($recLen == 0) {
                usleep(5000);
            } else {
                $this->_processMessage($buf, $remote_ip, $remote_port);
            }

            if (time() == $lastFlush || time() % $flushInterval !== 0) {
                continue;
            }
            $lastFlush = time();

            if ($pid !== false) {
                $exit = pcntl_waitpid($pid, $status, WNOHANG);
                if ($exit === 0) {
                    $this->log->debug("Process still running. Waiting another round for it to complete.");
                    continue;
                }
                if ($exit != $pid) {
                    $this->log->notice("Got wrong pid: $exit");
                }
                if ( ! pcntl_wifexited($status)) {
                    $this->log->notice( "Got failed exit status for job $pid: Returned $status");
                }
            }

            $pid = pcntl_fork();
            if ($pid == -1) {
                throw new \Exception("Cannot fork!");
            } else if ($pid) {
                $this->log->debug("Parent Process - resetting buffer");

                foreach ($this->mProcessors as $key => $value) {
                    $value->flush();
                }
            } else {
                // Child Process
                foreach ($this->mProcessors as $key => $value) {
                    $startTime = microtime(true);
                    $value->processData($lastFlush);
                    $this->log->debug("Processing " . get_class($value) . ": In " . round((microtime(true) - $startTime), 3) . " sec");
                }
                exit (0);
            }
        }


    }




}