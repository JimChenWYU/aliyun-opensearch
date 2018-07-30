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

class StructContext extends Context
{
    protected $first_ = true;
    protected $colon_ = true;
    private $p_;

    public function __construct($p)
    {
        $this->p_ = $p;
    }

    public function write()
    {
        if ($this->first_) {
            $this->first_ = false;
            $this->colon_ = true;
        } else {
            $this->p_->getTransport()->write(
                $this->colon_ ?
                TSimpleJSONProtocol::COLON :
                TSimpleJSONProtocol::COMMA
            );
            $this->colon_ = !$this->colon_;
        }
    }
}
