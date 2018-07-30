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
use Thrift\Factory\TStringFuncFactory;

/**
 * Sockets implementation of the TTransport interface.
 */
class TSocket extends TTransport
{
    /**
     * Handle to PHP socket.
     *
     * @var resource
     */
    protected $handle_ = null;

    /**
     * Remote hostname.
     *
     * @var string
     */
    protected $host_ = 'localhost';

    /**
     * Remote port.
     *
     * @var int
     */
    protected $port_ = '9090';

    /**
     * Send timeout in seconds.
     *
     * Combined with sendTimeoutUsec this is used for send timeouts.
     *
     * @var int
     */
    protected $sendTimeoutSec_ = 0;

    /**
     * Send timeout in microseconds.
     *
     * Combined with sendTimeoutSec this is used for send timeouts.
     *
     * @var int
     */
    protected $sendTimeoutUsec_ = 100000;

    /**
     * Recv timeout in seconds.
     *
     * Combined with recvTimeoutUsec this is used for recv timeouts.
     *
     * @var int
     */
    protected $recvTimeoutSec_ = 0;

    /**
     * Recv timeout in microseconds.
     *
     * Combined with recvTimeoutSec this is used for recv timeouts.
     *
     * @var int
     */
    protected $recvTimeoutUsec_ = 750000;

    /**
     * Persistent socket or plain?
     *
     * @var bool
     */
    protected $persist_ = false;

    /**
     * Debugging on?
     *
     * @var bool
     */
    protected $debug_ = false;

    /**
     * Debug handler.
     *
     * @var mixed
     */
    protected $debugHandler_ = null;

    /**
     * Socket constructor.
     *
     * @param string $host         Remote hostname
     * @param int    $port         Remote port
     * @param bool   $persist      Whether to use a persistent socket
     * @param string $debugHandler Function to call for error logging
     */
    public function __construct($host = 'localhost',
                              $port = 9090,
                              $persist = false,
                              $debugHandler = null)
    {
        $this->host_ = $host;
        $this->port_ = $port;
        $this->persist_ = $persist;
        $this->debugHandler_ = $debugHandler ? $debugHandler : 'error_log';
    }

    /**
     * @param resource $handle
     */
    public function setHandle($handle)
    {
        $this->handle_ = $handle;
    }

    /**
     * Sets the send timeout.
     *
     * @param int $timeout timeout in milliseconds
     */
    public function setSendTimeout($timeout)
    {
        $this->sendTimeoutSec_ = floor($timeout / 1000);
        $this->sendTimeoutUsec_ =
            ($timeout - ($this->sendTimeoutSec_ * 1000)) * 1000;
    }

    /**
     * Sets the receive timeout.
     *
     * @param int $timeout timeout in milliseconds
     */
    public function setRecvTimeout($timeout)
    {
        $this->recvTimeoutSec_ = floor($timeout / 1000);
        $this->recvTimeoutUsec_ =
            ($timeout - ($this->recvTimeoutSec_ * 1000)) * 1000;
    }

    /**
     * Sets debugging output on or off.
     *
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug_ = $debug;
    }

    /**
     * Get the host that this socket is connected to.
     *
     * @return string host
     */
    public function getHost()
    {
        return $this->host_;
    }

    /**
     * Get the remote port that this socket is connected to.
     *
     * @return int port
     */
    public function getPort()
    {
        return $this->port_;
    }

    /**
     * Tests whether this is open.
     *
     * @return bool true if the socket is open
     */
    public function isOpen()
    {
        return is_resource($this->handle_);
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

        if ($this->persist_) {
            $this->handle_ = @pfsockopen($this->host_,
                                   $this->port_,
                                   $errno,
                                   $errstr,
                                   $this->sendTimeoutSec_ + ($this->sendTimeoutUsec_ / 1000000));
        } else {
            $this->handle_ = @fsockopen($this->host_,
                                  $this->port_,
                                  $errno,
                                  $errstr,
                                  $this->sendTimeoutSec_ + ($this->sendTimeoutUsec_ / 1000000));
        }

        // Connect failed?
        if (false === $this->handle_) {
            $error = 'TSocket: Could not connect to '.$this->host_.':'.$this->port_.' ('.$errstr.' ['.$errno.'])';
            if ($this->debug_) {
                call_user_func($this->debugHandler_, $error);
            }
            throw new TException($error);
        }
    }

    /**
     * Closes the socket.
     */
    public function close()
    {
        if (!$this->persist_) {
            @fclose($this->handle_);
            $this->handle_ = null;
        }
    }

    /**
     * Read from the socket at most $len bytes.
     *
     * This method will not wait for all the requested data, it will return as
     * soon as any data is received.
     *
     * @param int $len maximum number of bytes to read
     *
     * @return string Binary data
     */
    public function read($len)
    {
        $null = null;
        $read = [$this->handle_];
        $readable = @stream_select($read, $null, $null, $this->recvTimeoutSec_, $this->recvTimeoutUsec_);

        if ($readable > 0) {
            $data = fread($this->handle_, $len);
            if (false === $data) {
                throw new TTransportException('TSocket: Could not read '.$len.' bytes from '.
                               $this->host_.':'.$this->port_);
            } elseif ('' == $data && feof($this->handle_)) {
                throw new TTransportException('TSocket read 0 bytes');
            }

            return $data;
        } elseif (0 === $readable) {
            throw new TTransportException('TSocket: timed out reading '.$len.' bytes from '.
                             $this->host_.':'.$this->port_);
        }
        throw new TTransportException('TSocket: Could not read '.$len.' bytes from '.
                             $this->host_.':'.$this->port_);
    }

    /**
     * Write to the socket.
     *
     * @param string $buf The data to write
     */
    public function write($buf)
    {
        $null = null;
        $write = [$this->handle_];

        // keep writing until all the data has been written
        while (TStringFuncFactory::create()->strlen($buf) > 0) {
            // wait for stream to become available for writing
            $writable = @stream_select($null, $write, $null, $this->sendTimeoutSec_, $this->sendTimeoutUsec_);
            if ($writable > 0) {
                // write buffer to stream
                $written = fwrite($this->handle_, $buf);
                if (-1 === $written || false === $written) {
                    throw new TTransportException('TSocket: Could not write '.TStringFuncFactory::create()->strlen($buf).' bytes '.
                                   $this->host_.':'.$this->port_);
                }
                // determine how much of the buffer is left to write
                $buf = TStringFuncFactory::create()->substr($buf, $written);
            } elseif (0 === $writable) {
                throw new TTransportException('TSocket: timed out writing '.TStringFuncFactory::create()->strlen($buf).' bytes from '.
                               $this->host_.':'.$this->port_);
            } else {
                throw new TTransportException('TSocket: Could not write '.TStringFuncFactory::create()->strlen($buf).' bytes '.
                                 $this->host_.':'.$this->port_);
            }
        }
    }

    /**
     * Flush output to the socket.
     *
     * Since read(), readAll() and write() operate on the sockets directly,
     * this is a no-op
     *
     * If you wish to have flushable buffering behaviour, wrap this TSocket
     * in a TBufferedTransport.
     */
    public function flush()
    {
        // no-op
    }
}
