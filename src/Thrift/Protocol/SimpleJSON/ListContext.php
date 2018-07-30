<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Protocol\SimpleJSON;

use Thrift\Protocol\TSimpleJSONProtocol;

class ListContext extends Context
{
    protected $first_ = true;
    private $p_;

    public function __construct($p)
    {
        $this->p_ = $p;
    }

    public function write()
    {
        if ($this->first_) {
            $this->first_ = false;
        } else {
            $this->p_->getTransport()->write(TSimpleJSONProtocol::COMMA);
        }
    }
}
