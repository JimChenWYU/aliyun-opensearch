<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift;

use Thrift\Exception\TException;
use Thrift\Protocol\TMultiplexedProtocol;
use Thrift\Protocol\TProtocol;
use Thrift\Protocol\TProtocolDecorator;
use Thrift\Type\TMessageType;

/**
 * <code>TMultiplexedProcessor</code> is a Processor allowing
 * a single <code>TServer</code> to provide multiple services.
 *
 * <p>To do so, you instantiate the processor and then register additional
 * processors with it, as shown in the following example:</p>
 *
 * <blockquote><code>
 *     $processor = new TMultiplexedProcessor();
 *
 *     processor->registerProcessor(
 *         "Calculator",
 *         new \tutorial\CalculatorProcessor(new CalculatorHandler()));
 *
 *     processor->registerProcessor(
 *         "WeatherReport",
 *         new \tutorial\WeatherReportProcessor(new WeatherReportHandler()));
 *
 *     $processor->process($protocol, $protocol);
 * </code></blockquote>
 */
class TMultiplexedProcessor
{
    private $serviceProcessorMap_;

    /**
     * 'Register' a service with this <code>TMultiplexedProcessor</code>.  This
     * allows us to broker requests to individual services by using the service
     * name to select them at request time.
     *
     * @param serviceName Name of a service, has to be identical to the name
     * declared in the Thrift IDL, e.g. "WeatherReport".
     * @param processor Implementation of a service, usually referred to
     * as "handlers", e.g. WeatherReportHandler implementing WeatherReport.Iface.
     */
    public function registerProcessor($serviceName, $processor)
    {
        $this->serviceProcessorMap_[$serviceName] = $processor;
    }

    /**
     * This implementation of <code>process</code> performs the following steps:.
     *
     * <ol>
     *     <li>Read the beginning of the message.</li>
     *     <li>Extract the service name from the message.</li>
     *     <li>Using the service name to locate the appropriate processor.</li>
     *     <li>Dispatch to the processor, with a decorated instance of TProtocol
     *         that allows readMessageBegin() to return the original Message.</li>
     * </ol>
     *
     * @throws TException if the message type is not CALL or ONEWAY, if
     *                    the service name was not found in the message, or if the service
     *                    name was not found in the service map
     */
    public function process(TProtocol $input, TProtocol $output)
    {
        /*
            Use the actual underlying protocol (e.g. TBinaryProtocol) to read the
            message header. This pulls the message "off the wire", which we'll
            deal with at the end of this method.
        */
        $input->readMessageBegin($fname, $mtype, $rseqid);

        if (TMessageType::CALL !== $mtype && TMessageType::ONEWAY != $mtype) {
            throw new TException('This should not have happened!?');
        }

        // Extract the service name and the new Message name.
        if (false === strpos($fname, TMultiplexedProtocol::SEPARATOR)) {
            throw new TException("Service name not found in message name: {$fname}. Did you ".
                'forget to use a TMultiplexProtocol in your client?');
        }
        list($serviceName, $messageName) = explode(':', $fname, 2);
        if (!array_key_exists($serviceName, $this->serviceProcessorMap_)) {
            throw new TException("Service name not found: {$serviceName}.  Did you forget ".
                'to call registerProcessor()?');
        }

        // Dispatch processing to the stored processor
        $processor = $this->serviceProcessorMap_[$serviceName];

        return $processor->process(
            new StoredMessageProtocol($input, $messageName, $mtype, $rseqid), $output
        );
    }
}

/**
 *  Our goal was to work with any protocol. In order to do that, we needed
 *  to allow them to call readMessageBegin() and get the Message in exactly
 *  the standard format, without the service name prepended to the Message name.
 */
class StoredMessageProtocol extends TProtocolDecorator
{
    private $fname_;
    private $mtype_;
    private $rseqid_;

    public function __construct(TProtocol $protocol, $fname, $mtype, $rseqid)
    {
        parent::__construct($protocol);
        $this->fname_ = $fname;
        $this->mtype_ = $mtype;
        $this->rseqid_ = $rseqid;
    }

    public function readMessageBegin(&$name, &$type, &$seqid)
    {
        $name = $this->fname_;
        $type = $this->mtype_;
        $seqid = $this->rseqid_;
    }
}
