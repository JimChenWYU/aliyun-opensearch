<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Factory;

use Thrift\Transport\TTransport;

class TTransportFactory
{
    /**
     * @static
     *
     * @param TTransport $transport
     *
     * @return TTransport
     */
    public static function getTransport(TTransport $transport)
    {
        return $transport;
    }
}
