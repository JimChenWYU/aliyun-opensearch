<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Protocol\JSON;

use Thrift\Protocol\TJSONProtocol;

class PairContext extends BaseContext
{
    private $first_ = true;
    private $colon_ = true;
    private $p_ = null;

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
            $this->p_->getTransport()->write($this->colon_ ? TJSONProtocol::COLON : TJSONProtocol::COMMA);
            $this->colon_ = !$this->colon_;
        }
    }

    public function read()
    {
        if ($this->first_) {
            $this->first_ = false;
            $this->colon_ = true;
        } else {
            $this->p_->readJSONSyntaxChar($this->colon_ ? TJSONProtocol::COLON : TJSONProtocol::COMMA);
            $this->colon_ = !$this->colon_;
        }
    }

    public function escapeNum()
    {
        return $this->colon_;
    }
}
