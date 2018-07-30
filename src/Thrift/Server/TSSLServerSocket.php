<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Server;

use Thrift\Transport\TSSLSocket;

/**
 * Socket implementation of a server agent.
 */
class TSSLServerSocket extends TServerSocket
{
    /**
     * Remote port.
     *
     * @var resource
     */
    protected $context_ = null;

    /**
     * ServerSocket constructor.
     *
     * @param string   $host    Host to listen on
     * @param int      $port    Port to listen on
     * @param resource $context Stream context
     */
    public function __construct($host = 'localhost', $port = 9090, $context = null)
    {
        $ssl_host = $this->getSSLHost($host);
        parent::__construct($ssl_host, $port);
        $this->context_ = $context;
    }

    public function getSSLHost($host)
    {
        $transport_protocol_loc = strpos($host, '://');
        if (false === $transport_protocol_loc) {
            $host = 'ssl://'.$host;
        }

        return $host;
    }

    /**
     * Opens a new socket server handle.
     */
    public function listen()
    {
        $this->listener_ = @stream_socket_server(
      $this->host_.':'.$this->port_,
      $errno,
      $errstr,
      STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
      $this->context_);
    }

    /**
     * Implementation of accept. If not client is accepted in the given time.
     *
     * @return TSocket
     */
    protected function acceptImpl()
    {
        $handle = @stream_socket_accept($this->listener_, $this->acceptTimeout_ / 1000.0);
        if (!$handle) {
            return null;
        }

        $socket = new TSSLSocket();
        $socket->setHandle($handle);

        return $socket;
    }
}
