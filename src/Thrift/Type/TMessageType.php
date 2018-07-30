<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Type;

/**
 * Message types for RPC.
 */
class TMessageType
{
    const CALL = 1;
    const REPLY = 2;
    const EXCEPTION = 3;
    const ONEWAY = 4;
}
