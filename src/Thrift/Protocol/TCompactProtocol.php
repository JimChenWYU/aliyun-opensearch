<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Protocol;

use Thrift\Exception\TProtocolException;
use Thrift\Factory\TStringFuncFactory;
use Thrift\Type\TType;

/**
 * Compact implementation of the Thrift protocol.
 */
class TCompactProtocol extends TProtocol
{
    const COMPACT_STOP = 0x00;
    const COMPACT_TRUE = 0x01;
    const COMPACT_FALSE = 0x02;
    const COMPACT_BYTE = 0x03;
    const COMPACT_I16 = 0x04;
    const COMPACT_I32 = 0x05;
    const COMPACT_I64 = 0x06;
    const COMPACT_DOUBLE = 0x07;
    const COMPACT_BINARY = 0x08;
    const COMPACT_LIST = 0x09;
    const COMPACT_SET = 0x0A;
    const COMPACT_MAP = 0x0B;
    const COMPACT_STRUCT = 0x0C;

    const STATE_CLEAR = 0;
    const STATE_FIELD_WRITE = 1;
    const STATE_VALUE_WRITE = 2;
    const STATE_CONTAINER_WRITE = 3;
    const STATE_BOOL_WRITE = 4;
    const STATE_FIELD_READ = 5;
    const STATE_CONTAINER_READ = 6;
    const STATE_VALUE_READ = 7;
    const STATE_BOOL_READ = 8;

    const VERSION_MASK = 0x1f;
    const VERSION = 1;
    const PROTOCOL_ID = 0x82;
    const TYPE_MASK = 0xe0;
    const TYPE_BITS = 0x07;
    const TYPE_SHIFT_AMOUNT = 5;

    protected static $ctypes = [
    TType::STOP => TCompactProtocol::COMPACT_STOP,
    TType::BOOL => TCompactProtocol::COMPACT_TRUE, // used for collection
    TType::BYTE => TCompactProtocol::COMPACT_BYTE,
    TType::I16 => TCompactProtocol::COMPACT_I16,
    TType::I32 => TCompactProtocol::COMPACT_I32,
    TType::I64 => TCompactProtocol::COMPACT_I64,
    TType::DOUBLE => TCompactProtocol::COMPACT_DOUBLE,
    TType::STRING => TCompactProtocol::COMPACT_BINARY,
    TType::STRUCT => TCompactProtocol::COMPACT_STRUCT,
    TType::LST => TCompactProtocol::COMPACT_LIST,
    TType::SET => TCompactProtocol::COMPACT_SET,
    TType::MAP => TCompactProtocol::COMPACT_MAP,
  ];

    protected static $ttypes = [
    TCompactProtocol::COMPACT_STOP => TType::STOP,
    TCompactProtocol::COMPACT_TRUE => TType::BOOL, // used for collection
    TCompactProtocol::COMPACT_FALSE => TType::BOOL,
    TCompactProtocol::COMPACT_BYTE => TType::BYTE,
    TCompactProtocol::COMPACT_I16 => TType::I16,
    TCompactProtocol::COMPACT_I32 => TType::I32,
    TCompactProtocol::COMPACT_I64 => TType::I64,
    TCompactProtocol::COMPACT_DOUBLE => TType::DOUBLE,
    TCompactProtocol::COMPACT_BINARY => TType::STRING,
    TCompactProtocol::COMPACT_STRUCT => TType::STRUCT,
    TCompactProtocol::COMPACT_LIST => TType::LST,
    TCompactProtocol::COMPACT_SET => TType::SET,
    TCompactProtocol::COMPACT_MAP => TType::MAP,
  ];

    protected $state = TCompactProtocol::STATE_CLEAR;
    protected $lastFid = 0;
    protected $boolFid = null;
    protected $boolValue = null;
    protected $structs = [];
    protected $containers = [];

    // Some varint / zigzag helper methods
    public function toZigZag($n, $bits)
    {
        return ($n << 1) ^ ($n >> ($bits - 1));
    }

    public function fromZigZag($n)
    {
        return ($n >> 1) ^ -($n & 1);
    }

    public function getVarint($data)
    {
        $out = '';
        while (true) {
            if (0 === ($data & ~0x7f)) {
                $out .= chr($data);
                break;
            }
            $out .= chr(($data & 0xff) | 0x80);
            $data = $data >> 7;
        }

        return $out;
    }

    public function writeVarint($data)
    {
        $out = $this->getVarint($data);
        $result = TStringFuncFactory::create()->strlen($out);
        $this->trans_->write($out, $result);

        return $result;
    }

    public function readVarint(&$result)
    {
        $idx = 0;
        $shift = 0;
        $result = 0;
        while (true) {
            $x = $this->trans_->readAll(1);
            $arr = unpack('C', $x);
            $byte = $arr[1];
            ++$idx;
            $result |= ($byte & 0x7f) << $shift;
            if (0 === ($byte >> 7)) {
                return $idx;
            }
            $shift += 7;
        }

        return $idx;
    }

    public function __construct($trans)
    {
        parent::__construct($trans);
    }

    public function writeMessageBegin($name, $type, $seqid)
    {
        $written =
      $this->writeUByte(TCompactProtocol::PROTOCOL_ID) +
      $this->writeUByte(TCompactProtocol::VERSION |
                        ($type << TCompactProtocol::TYPE_SHIFT_AMOUNT)) +
      $this->writeVarint($seqid) +
      $this->writeString($name);
        $this->state = TCompactProtocol::STATE_VALUE_WRITE;

        return $written;
    }

    public function writeMessageEnd()
    {
        $this->state = TCompactProtocol::STATE_CLEAR;

        return 0;
    }

    public function writeStructBegin($name)
    {
        $this->structs[] = [$this->state, $this->lastFid];
        $this->state = TCompactProtocol::STATE_FIELD_WRITE;
        $this->lastFid = 0;

        return 0;
    }

    public function writeStructEnd()
    {
        $old_values = array_pop($this->structs);
        $this->state = $old_values[0];
        $this->lastFid = $old_values[1];

        return 0;
    }

    public function writeFieldStop()
    {
        return $this->writeByte(0);
    }

    public function writeFieldHeader($type, $fid)
    {
        $written = 0;
        $delta = $fid - $this->lastFid;
        if (0 < $delta && $delta <= 15) {
            $written = $this->writeUByte(($delta << 4) | $type);
        } else {
            $written = $this->writeByte($type) +
        $this->writeI16($fid);
        }
        $this->lastFid = $fid;

        return $written;
    }

    public function writeFieldBegin($field_name, $field_type, $field_id)
    {
        if (TTYPE::BOOL == $field_type) {
            $this->state = TCompactProtocol::STATE_BOOL_WRITE;
            $this->boolFid = $field_id;

            return 0;
        }
        $this->state = TCompactProtocol::STATE_VALUE_WRITE;

        return $this->writeFieldHeader(self::$ctypes[$field_type], $field_id);
    }

    public function writeFieldEnd()
    {
        $this->state = TCompactProtocol::STATE_FIELD_WRITE;

        return 0;
    }

    public function writeCollectionBegin($etype, $size)
    {
        $written = 0;
        if ($size <= 14) {
            $written = $this->writeUByte($size << 4 |
                                    self::$ctypes[$etype]);
        } else {
            $written = $this->writeUByte(0xf0 |
                                   self::$ctypes[$etype]) +
        $this->writeVarint($size);
        }
        $this->containers[] = $this->state;
        $this->state = TCompactProtocol::STATE_CONTAINER_WRITE;

        return $written;
    }

    public function writeMapBegin($key_type, $val_type, $size)
    {
        $written = 0;
        if (0 == $size) {
            $written = $this->writeByte(0);
        } else {
            $written = $this->writeVarint($size) +
        $this->writeUByte(self::$ctypes[$key_type] << 4 |
                          self::$ctypes[$val_type]);
        }
        $this->containers[] = $this->state;

        return $written;
    }

    public function writeCollectionEnd()
    {
        $this->state = array_pop($this->containers);

        return 0;
    }

    public function writeMapEnd()
    {
        return $this->writeCollectionEnd();
    }

    public function writeListBegin($elem_type, $size)
    {
        return $this->writeCollectionBegin($elem_type, $size);
    }

    public function writeListEnd()
    {
        return $this->writeCollectionEnd();
    }

    public function writeSetBegin($elem_type, $size)
    {
        return $this->writeCollectionBegin($elem_type, $size);
    }

    public function writeSetEnd()
    {
        return $this->writeCollectionEnd();
    }

    public function writeBool($value)
    {
        if (TCompactProtocol::STATE_BOOL_WRITE == $this->state) {
            $ctype = TCompactProtocol::COMPACT_FALSE;
            if ($value) {
                $ctype = TCompactProtocol::COMPACT_TRUE;
            }

            return $this->writeFieldHeader($ctype, $this->boolFid);
        } elseif (TCompactProtocol::STATE_CONTAINER_WRITE == $this->state) {
            return $this->writeByte($value ? 1 : 0);
        }
        throw new TProtocolException('Invalid state in compact protocol');
    }

    public function writeByte($value)
    {
        $data = pack('c', $value);
        $this->trans_->write($data, 1);

        return 1;
    }

    public function writeUByte($byte)
    {
        $this->trans_->write(pack('C', $byte), 1);

        return 1;
    }

    public function writeI16($value)
    {
        $thing = $this->toZigZag($value, 16);

        return $this->writeVarint($thing);
    }

    public function writeI32($value)
    {
        $thing = $this->toZigZag($value, 32);

        return $this->writeVarint($thing);
    }

    public function writeDouble($value)
    {
        $data = pack('d', $value);
        $this->trans_->write($data, 8);

        return 8;
    }

    public function writeString($value)
    {
        $len = TStringFuncFactory::create()->strlen($value);
        $result = $this->writeVarint($len);
        if ($len) {
            $this->trans_->write($value, $len);
        }

        return $result + $len;
    }

    public function readFieldBegin(&$name, &$field_type, &$field_id)
    {
        $result = $this->readUByte($compact_type_and_delta);

        $compact_type = $compact_type_and_delta & 0x0f;

        if (TType::STOP == $compact_type) {
            $field_type = $compact_type;
            $field_id = 0;

            return $result;
        }
        $delta = $compact_type_and_delta >> 4;
        if (0 == $delta) {
            $result += $this->readI16($field_id);
        } else {
            $field_id = $this->lastFid + $delta;
        }
        $this->lastFid = $field_id;
        $field_type = $this->getTType($compact_type);

        if (TCompactProtocol::COMPACT_TRUE == $compact_type) {
            $this->state = TCompactProtocol::STATE_BOOL_READ;
            $this->boolValue = true;
        } elseif (TCompactProtocol::COMPACT_FALSE == $compact_type) {
            $this->state = TCompactProtocol::STATE_BOOL_READ;
            $this->boolValue = false;
        } else {
            $this->state = TCompactProtocol::STATE_VALUE_READ;
        }

        return $result;
    }

    public function readFieldEnd()
    {
        $this->state = TCompactProtocol::STATE_FIELD_READ;

        return 0;
    }

    public function readUByte(&$value)
    {
        $data = $this->trans_->readAll(1);
        $arr = unpack('C', $data);
        $value = $arr[1];

        return 1;
    }

    public function readByte(&$value)
    {
        $data = $this->trans_->readAll(1);
        $arr = unpack('c', $data);
        $value = $arr[1];

        return 1;
    }

    public function readZigZag(&$value)
    {
        $result = $this->readVarint($value);
        $value = $this->fromZigZag($value);

        return $result;
    }

    public function readMessageBegin(&$name, &$type, &$seqid)
    {
        $protoId = 0;
        $result = $this->readUByte($protoId);
        if (TCompactProtocol::PROTOCOL_ID != $protoId) {
            throw new TProtocolException('Bad protocol id in TCompact message');
        }
        $verType = 0;
        $result += $this->readUByte($verType);
        $type = ($verType >> TCompactProtocol::TYPE_SHIFT_AMOUNT) & TCompactProtocol::TYPE_BITS;
        $version = $verType & TCompactProtocol::VERSION_MASK;
        if (TCompactProtocol::VERSION != $version) {
            throw new TProtocolException('Bad version in TCompact message');
        }
        $result += $this->readVarint($seqid);
        $result += $this->readString($name);

        return $result;
    }

    public function readMessageEnd()
    {
        return 0;
    }

    public function readStructBegin(&$name)
    {
        $name = ''; // unused
        $this->structs[] = [$this->state, $this->lastFid];
        $this->state = TCompactProtocol::STATE_FIELD_READ;
        $this->lastFid = 0;

        return 0;
    }

    public function readStructEnd()
    {
        $last = array_pop($this->structs);
        $this->state = $last[0];
        $this->lastFid = $last[1];

        return 0;
    }

    public function readCollectionBegin(&$type, &$size)
    {
        $sizeType = 0;
        $result = $this->readUByte($sizeType);
        $size = $sizeType >> 4;
        $type = $this->getTType($sizeType);
        if (15 == $size) {
            $result += $this->readVarint($size);
        }
        $this->containers[] = $this->state;
        $this->state = TCompactProtocol::STATE_CONTAINER_READ;

        return $result;
    }

    public function readMapBegin(&$key_type, &$val_type, &$size)
    {
        $result = $this->readVarint($size);
        $types = 0;
        if ($size > 0) {
            $result += $this->readUByte($types);
        }
        $val_type = $this->getTType($types);
        $key_type = $this->getTType($types >> 4);
        $this->containers[] = $this->state;
        $this->state = TCompactProtocol::STATE_CONTAINER_READ;

        return $result;
    }

    public function readCollectionEnd()
    {
        $this->state = array_pop($this->containers);

        return 0;
    }

    public function readMapEnd()
    {
        return $this->readCollectionEnd();
    }

    public function readListBegin(&$elem_type, &$size)
    {
        return $this->readCollectionBegin($elem_type, $size);
    }

    public function readListEnd()
    {
        return $this->readCollectionEnd();
    }

    public function readSetBegin(&$elem_type, &$size)
    {
        return $this->readCollectionBegin($elem_type, $size);
    }

    public function readSetEnd()
    {
        return $this->readCollectionEnd();
    }

    public function readBool(&$value)
    {
        if (TCompactProtocol::STATE_BOOL_READ == $this->state) {
            $value = $this->boolValue;

            return 0;
        } elseif (TCompactProtocol::STATE_CONTAINER_READ == $this->state) {
            return $this->readByte($value);
        }
        throw new TProtocolException('Invalid state in compact protocol');
    }

    public function readI16(&$value)
    {
        return $this->readZigZag($value);
    }

    public function readI32(&$value)
    {
        return $this->readZigZag($value);
    }

    public function readDouble(&$value)
    {
        $data = $this->trans_->readAll(8);
        $arr = unpack('d', $data);
        $value = $arr[1];

        return 8;
    }

    public function readString(&$value)
    {
        $result = $this->readVarint($len);
        if ($len) {
            $value = $this->trans_->readAll($len);
        } else {
            $value = '';
        }

        return $result + $len;
    }

    public function getTType($byte)
    {
        return self::$ttypes[$byte & 0x0f];
    }

    // If we are on a 32bit architecture we have to explicitly deal with
    // 64-bit twos-complement arithmetic since PHP wants to treat all ints
    // as signed and any int over 2^31 - 1 as a float

    // Read and write I64 as two 32 bit numbers $hi and $lo

    public function readI64(&$value)
    {
        // Read varint from wire
        $hi = 0;
        $lo = 0;

        $idx = 0;
        $shift = 0;

        while (true) {
            $x = $this->trans_->readAll(1);
            $arr = unpack('C', $x);
            $byte = $arr[1];
            ++$idx;
            // Shift hi and lo together.
            if ($shift < 28) {
                $lo |= (($byte & 0x7f) << $shift);
            } elseif (28 == $shift) {
                $lo |= (($byte & 0x0f) << 28);
                $hi |= (($byte & 0x70) >> 4);
            } else {
                $hi |= (($byte & 0x7f) << ($shift - 32));
            }
            if (0 === ($byte >> 7)) {
                break;
            }
            $shift += 7;
        }

        // Now, unzig it.
        $xorer = 0;
        if ($lo & 1) {
            $xorer = 0xffffffff;
        }
        $lo = ($lo >> 1) & 0x7fffffff;
        $lo = $lo | (($hi & 1) << 31);
        $hi = ($hi >> 1) ^ $xorer;
        $lo = $lo ^ $xorer;

        // Now put $hi and $lo back together
        $isNeg = $hi < 0 || $hi & 0x80000000;

        // Check for a negative
        if ($isNeg) {
            $hi = ~$hi & (int) 0xffffffff;
            $lo = ~$lo & (int) 0xffffffff;

            if ($lo == (int) 0xffffffff) {
                ++$hi;
                $lo = 0;
            } else {
                ++$lo;
            }
        }

        // Force 32bit words in excess of 2G to be positive - we deal with sign
        // explicitly below

        if ($hi & (int) 0x80000000) {
            $hi &= (int) 0x7fffffff;
            $hi += 0x80000000;
        }

        if ($lo & (int) 0x80000000) {
            $lo &= (int) 0x7fffffff;
            $lo += 0x80000000;
        }

        // Create as negative value first, since we can store -2^63 but not 2^63
        $value = -$hi * 4294967296 - $lo;

        if (!$isNeg) {
            $value = -$value;
        }

        return $idx;
    }

    public function writeI64($value)
    {
        // If we are in an I32 range, use the easy method below.
        if (($value > 4294967296) || ($value < -4294967296)) {
            // Convert $value to $hi and $lo
            $neg = $value < 0;

            if ($neg) {
                $value *= -1;
            }

            $hi = (int) $value >> 32;
            $lo = (int) $value & 0xffffffff;

            if ($neg) {
                $hi = ~$hi;
                $lo = ~$lo;
                if (($lo & (int) 0xffffffff) == (int) 0xffffffff) {
                    $lo = 0;
                    ++$hi;
                } else {
                    ++$lo;
                }
            }

            // Now do the zigging and zagging.
            $xorer = 0;
            if ($neg) {
                $xorer = 0xffffffff;
            }
            $lowbit = ($lo >> 31) & 1;
            $hi = ($hi << 1) | $lowbit;
            $lo = ($lo << 1);
            $lo = ($lo ^ $xorer) & 0xffffffff;
            $hi = ($hi ^ $xorer) & 0xffffffff;

            // now write out the varint, ensuring we shift both hi and lo
            $out = '';
            while (true) {
                if (0 === ($lo & ~0x7f) &&
           0 === $hi) {
                    $out .= chr($lo);
                    break;
                }
                $out .= chr(($lo & 0xff) | 0x80);
                $lo = $lo >> 7;
                $lo = $lo | ($hi << 25);
                $hi = $hi >> 7;
                // Right shift carries sign, but we don't want it to.
                $hi = $hi & (127 << 25);
            }

            $ret = TStringFuncFactory::create()->strlen($out);
            $this->trans_->write($out, $ret);

            return $ret;
        }

        return $this->writeVarint($this->toZigZag($value, 64));
    }
}
