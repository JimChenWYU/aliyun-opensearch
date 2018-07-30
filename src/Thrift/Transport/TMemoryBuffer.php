<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Transport;

use Thrift\Exception\TTransportException;
use Thrift\Factory\TStringFuncFactory;

/**
 * A memory buffer is a tranpsort that simply reads from and writes to an
 * in-memory string buffer. Anytime you call write on it, the data is simply
 * placed into a buffer, and anytime you call read, data is read from that
 * buffer.
 */
class TMemoryBuffer extends TTransport
{
    /**
     * Constructor. Optionally pass an initial value
     * for the buffer.
     */
    public function __construct($buf = '')
    {
        $this->buf_ = $buf;
    }

    protected $buf_ = '';

    public function isOpen()
    {
        return true;
    }

    public function open()
    {
    }

    public function close()
    {
    }

    public function write($buf)
    {
        $this->buf_ .= $buf;
    }

    public function read($len)
    {
        $bufLength = TStringFuncFactory::create()->strlen($this->buf_);

        if (0 === $bufLength) {
            throw new TTransportException('TMemoryBuffer: Could not read '.
                                    $len.' bytes from buffer.',
                                    TTransportException::UNKNOWN);
        }

        if ($bufLength <= $len) {
            $ret = $this->buf_;
            $this->buf_ = '';

            return $ret;
        }

        $ret = TStringFuncFactory::create()->substr($this->buf_, 0, $len);
        $this->buf_ = TStringFuncFactory::create()->substr($this->buf_, $len);

        return $ret;
    }

    public function getBuffer()
    {
        return $this->buf_;
    }

    public function available()
    {
        return TStringFuncFactory::create()->strlen($this->buf_);
    }

    public function putBack($data)
    {
        $this->buf_ = $data.$this->buf_;
    }
}
