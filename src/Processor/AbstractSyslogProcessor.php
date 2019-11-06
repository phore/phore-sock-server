<?php
/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 03.08.17
 * Time: 12:41
 */

namespace Phore\SockServer\Processor;



use Phore\SockServer\SocketServerProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


/**
 * A RFC3164 compliant Syslog Processor
 *
 * https://tools.ietf.org/html/rfc3164
 *
 * Class SyslogProcessor
 * @package Phore\SockServer\Processor
 */
abstract class AbstractSyslogProcessor implements SocketServerProcessor
{

    protected $buffer = [];

    /**
     * @var Logger
     */
    protected $logger;
    
    public function __construct(LoggerInterface $logger = null)
    {
        if ($logger === null)
            $logger = new NullLogger();
        $this->logger = $logger;
    }


    /**
     * Filter a incoming syslog message (e.g. with json)
     *
     * If false is returned the message will be skipped.
     *
     * This method is called by injectStringMessage on every new inbound message.
     *
     * @param string $message
     * @return mixed
     */
    protected function filterMessage(string $message)
    {
        return $message; // Do stuff in implementating class
    }


    public function injectStringMessage($senderIp, $senderPort, string $message) : bool
    {
        if (substr($message, 0, 1) !== "<") {
            return false;
        }
        if (($posx = strpos($message, ">")) === false) {
            return false;
        }

        $data = [
            "timestamp" => microtime(true),
            "clientIp" => $senderIp,
            "syslogDate" => null,
            "hostname" => null,
            "system" => null,
            "facility" => null,
            "severity" => null,
            "message" => null
        ];

        $id = (int)substr($message, 1, $posx-1);
        $data["severity"] = $id % 8; // see https://tools.ietf.org/html/rfc3164#section-4.1.1
        $data["facility"] = ($id - $data["severity"]) / 8;

        $message = substr($message, $posx+1);

        $data["syslogDate"] = substr($message, 0, 15);
        $message = substr($message, 16);

        list($data["hostname"], $data["system"]) = explode(" ", substr($message, 0, $msgStartIndex = strpos($message, ":")));

        $messageFiltered = $this->filterMessage(substr($message, $msgStartIndex+2));

        if ($messageFiltered === false || $messageFiltered === null) {
            return false;
        }
        $data["message"] = $messageFiltered;
        $this->buffer[] = $data;
        return true;
    }

    public function flush()
    {
        unset ($this->buffer);
        $this->buffer = [];
    }
}
