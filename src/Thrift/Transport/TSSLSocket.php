<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Transport;

use Thrift\Exception\TException;
use Thrift\Exception\TTransportException;

/**
 * Sockets implementation of the TTransport interface.
 */
class TSSLSocket extends TSocket
{
    /**
     * Remote port.
     *
     * @var resource
     */
    protected $context_ = null;

    /**
     * Socket constructor.
     *
     * @param string   $host         Remote hostname
     * @param int      $port         Remote port
     * @param resource $context      Stream context
     * @param bool     $persist      Whether to use a persistent socket
     * @param string   $debugHandler Function to call for error logging
     */
    public function __construct($host = 'localhost',
                              $port = 9090,
                              $context = null,
                              $debugHandler = null)
    {
        $this->host_ = $this->getSSLHost($host);
        $this->port_ = $port;
        $this->context_ = $context;
        $this->debugHandler_ = $debugHandler ? $debugHandler : 'error_log';
    }

    /**
     * Creates a host name with SSL transport protocol
     * if no transport protocol already specified in
     * the host name.
     *
     * @param string $host Host to listen on
     *
     * @return string $host   Host name with transport protocol
     */
    private function getSSLHost($host)
    {
        $transport_protocol_loc = strpos($host, '://');
        if (false === $transport_protocol_loc) {
            $host = 'ssl://'.$host;
        }

        return $host;
    }

    /**
     * Connects the socket.
     */
    public function open()
    {
        if ($this->isOpen()) {
            throw new TTransportException('Socket already connected', TTransportException::ALREADY_OPEN);
        }

        if (empty($this->host_)) {
            throw new TTransportException('Cannot open null host', TTransportException::NOT_OPEN);
        }

        if ($this->port_ <= 0) {
            throw new TTransportException('Cannot open without port', TTransportException::NOT_OPEN);
        }

        $this->handle_ = @stream_socket_client($this->host_.':'.$this->port_,
                                          $errno,
                                          $errstr,
                                          $this->sendTimeoutSec_ + ($this->sendTimeoutUsec_ / 1000000),
                                          STREAM_CLIENT_CONNECT,
                                          $this->context_);

        // Connect failed?
        if (false === $this->handle_) {
            $error = 'TSocket: Could not connect to '.$this->host_.':'.$this->port_.' ('.$errstr.' ['.$errno.'])';
            if ($this->debug_) {
                call_user_func($this->debugHandler_, $error);
            }
            throw new TException($error);
        }
    }
}
