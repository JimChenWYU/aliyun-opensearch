<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Transport;

use Thrift\Factory\TStringFuncFactory;

/**
 * Base interface for a transport agent.
 */
abstract class TTransport
{
    /**
     * Whether this transport is open.
     *
     * @return bool true if open
     */
    abstract public function isOpen();

    /**
     * Open the transport for reading/writing.
     *
     * @throws TTransportException if cannot open
     */
    abstract public function open();

    /**
     * Close the transport.
     */
    abstract public function close();

    /**
     * Read some data into the array.
     *
     * @param int $len How much to read
     *
     * @return string The data that has been read
     *
     * @throws TTransportException if cannot read any more data
     */
    abstract public function read($len);

    /**
     * Guarantees that the full amount of data is read.
     *
     * @return string The data, of exact length
     *
     * @throws TTransportException if cannot read data
     */
    public function readAll($len)
    {
        // return $this->read($len);

        $data = '';
        $got = 0;
        while (($got = TStringFuncFactory::create()->strlen($data)) < $len) {
            $data .= $this->read($len - $got);
        }

        return $data;
    }

    /**
     * Writes the given data out.
     *
     * @param string $buf The data to write
     *
     * @throws TTransportException if writing fails
     */
    abstract public function write($buf);

    /**
     * Flushes any pending data out of a buffer.
     *
     * @throws TTransportException if a writing error occurs
     */
    public function flush()
    {
    }
}
