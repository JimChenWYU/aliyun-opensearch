<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\StringFunc;

class Mbstring implements TStringFunc
{
    public function substr($str, $start, $length = null)
    {
        /*
         * We need to set the charset parameter, which is the second
         * optional parameter and the first optional parameter can't
         * be null or false as a "magic" value because that would
         * cause an empty string to be returned, so we need to
         * actually calculate the proper length value.
         */
        if (null === $length) {
            $length = $this->strlen($str) - $start;
        }

        return mb_substr($str, $start, $length, '8bit');
    }

    public function strlen($str)
    {
        return mb_strlen($str, '8bit');
    }
}
