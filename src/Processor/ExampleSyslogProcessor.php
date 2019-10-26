<?php


namespace Phore\SockServer\Processor;


class ExampleSyslogProcessor extends AbstractSyslogProcessor
{
    /**
     * @param string $message
     * @return mixed|string
     */
    protected function filterMessage(string $message)
    {
        return $message;
    }

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
    public function processData(int $flushTimestamp)
    {
        print_r ($this->buffer);
    }


}