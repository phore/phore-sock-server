<?php
/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 18.02.17
 * Time: 20:36
 */

namespace Phore\SockServer;



interface SocketServerProcessor
{

    public function injectStringMessage ($senderIp, $senderPort, string $message) : bool;

    public function flush();

    /**
     * Process the data asyncron.
     *
     * Make sure DB Connections are re-established before writing to them!
     *
     *
     *
     * @param int $flushTimestamp
     * @return mixed
     */
    public function processData(int $flushTimestamp);

}