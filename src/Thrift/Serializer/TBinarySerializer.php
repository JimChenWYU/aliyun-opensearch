<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Serializer;

use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Transport\TMemoryBuffer;
use Thrift\Type\TMessageType;

/**
 * Utility class for serializing and deserializing
 * a thrift object using TBinaryProtocolAccelerated.
 */
class TBinarySerializer
{
    // NOTE(rmarin): Because thrift_protocol_write_binary
    // adds a begin message prefix, you cannot specify
    // a transport in which to serialize an object. It has to
    // be a string. Otherwise we will break the compatibility with
    // normal deserialization.
    public static function serialize($object)
    {
        $transport = new TMemoryBuffer();
        $protocol = new TBinaryProtocolAccelerated($transport);
        if (function_exists('thrift_protocol_write_binary')) {
            thrift_protocol_write_binary($protocol, $object->getName(),
                                   TMessageType::REPLY, $object,
                                   0, $protocol->isStrictWrite());

            $protocol->readMessageBegin($unused_name, $unused_type,
                                  $unused_seqid);
        } else {
            $object->write($protocol);
        }
        $protocol->getTransport()->flush();

        return $transport->getBuffer();
    }

    public static function deserialize($string_object, $class_name, $buffer_size = 8192)
    {
        $transport = new TMemoryBuffer();
        $protocol = new TBinaryProtocolAccelerated($transport);
        if (function_exists('thrift_protocol_read_binary')) {
            // NOTE (t.heintz) TBinaryProtocolAccelerated internally wraps our TMemoryBuffer in a
            // TBufferedTransport, so we have to retrieve it again or risk losing data when writing
            // less than 512 bytes to the transport (see the comment there as well).
            // @see THRIFT-1579
            $protocol->writeMessageBegin('', TMessageType::REPLY, 0);
            $protocolTransport = $protocol->getTransport();
            $protocolTransport->write($string_object);
            $protocolTransport->flush();

            return thrift_protocol_read_binary($protocol, $class_name,
                                          $protocol->isStrictRead(),
                                          $buffer_size);
        }
        $transport->write($string_object);
        $object = new $class_name();
        $object->read($protocol);

        return $object;
    }
}
