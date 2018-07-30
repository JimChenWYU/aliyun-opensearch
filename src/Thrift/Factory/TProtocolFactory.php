<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Factory;

/**
 * Protocol factory creates protocol objects from transports.
 */
interface TProtocolFactory
{
    /**
     * Build a protocol from the base transport.
     *
     * @return Thrift\Protocol\TProtocol protocol
     */
    public function getProtocol($trans);
}
