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
 * Data types that can be sent via Thrift.
 */
class TType
{
    const STOP = 0;
    const VOID = 1;
    const BOOL = 2;
    const BYTE = 3;
    const I08 = 3;
    const DOUBLE = 4;
    const I16 = 6;
    const I32 = 8;
    const I64 = 10;
    const STRING = 11;
    const UTF7 = 11;
    const STRUCT = 12;
    const MAP = 13;
    const SET = 14;
    const LST = 15;    // N.B. cannot use LIST keyword in PHP!
    const UTF8 = 16;
    const UTF16 = 17;
}
