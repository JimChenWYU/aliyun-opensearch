<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Server;

use Thrift\Exception\TTransportException;

/**
 * Generic class for Server agent.
 */
abstract class TServerTransport
{
    /**
     * List for new clients.
     *
     * @abstract
     */
    abstract public function listen();

    /**
     * Close the server.
     *
     * @abstract
     */
    abstract public function close();

    /**
     * Subclasses should use this to implement
     * accept.
     *
     * @abstract
     *
     * @return TTransport
     */
    abstract protected function acceptImpl();

    /**
     * Uses the accept implemtation. If null is returned, an
     * exception is thrown.
     *
     * @throws TTransportException
     *
     * @return TTransport
     */
    public function accept()
    {
        $transport = $this->acceptImpl();

        if (null == $transport) {
            throw new TTransportException('accept() may not return NULL');
        }

        return $transport;
    }
}
