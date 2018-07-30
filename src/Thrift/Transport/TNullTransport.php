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

/**
 * Transport that only accepts writes and ignores them.
 * This is useful for measuring the serialized size of structures.
 */
class TNullTransport extends TTransport
{
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

    public function read($len)
    {
        throw new TTransportException("Can't read from TNullTransport.");
    }

    public function write($buf)
    {
    }
}
