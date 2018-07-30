<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Server;

use Thrift\Exception\TException;
use Thrift\Exception\TTransportException;
use Thrift\Transport\TTransport;

/**
 * A forking implementation of a Thrift server.
 */
class TForkingServer extends TServer
{
    /**
     * Flag for the main serving loop.
     *
     * @var bool
     */
    private $stop_ = false;

    /**
     * List of children.
     *
     * @var array
     */
    protected $children_ = [];

    /**
     * Listens for new client using the supplied
     * transport. We fork when a new connection
     * arrives.
     */
    public function serve()
    {
        $this->transport_->listen();

        while (!$this->stop_) {
            try {
                $transport = $this->transport_->accept();

                if (null != $transport) {
                    $pid = pcntl_fork();

                    if ($pid > 0) {
                        $this->handleParent($transport, $pid);
                    } elseif (0 === $pid) {
                        $this->handleChild($transport);
                    } else {
                        throw new TException('Failed to fork');
                    }
                }
            } catch (TTransportException $e) {
            }

            $this->collectChildren();
        }
    }

    /**
     * Code run by the parent.
     *
     * @param TTransport $transport
     * @param int        $pid
     */
    private function handleParent(TTransport $transport, $pid)
    {
        $this->children_[$pid] = $transport;
    }

    /**
     * Code run by the child.
     *
     * @param TTransport $transport
     */
    private function handleChild(TTransport $transport)
    {
        try {
            $inputTransport = $this->inputTransportFactory_->getTransport($transport);
            $outputTransport = $this->outputTransportFactory_->getTransport($transport);
            $inputProtocol = $this->inputProtocolFactory_->getProtocol($inputTransport);
            $outputProtocol = $this->outputProtocolFactory_->getProtocol($outputTransport);
            while ($this->processor_->process($inputProtocol, $outputProtocol)) {
            }
            @$transport->close();
        } catch (TTransportException $e) {
        }

        exit(0);
    }

    /**
     * Collects any children we may have.
     */
    private function collectChildren()
    {
        foreach ($this->children_ as $pid => $transport) {
            if (pcntl_waitpid($pid, $status, WNOHANG) > 0) {
                unset($this->children_[$pid]);
                if ($transport) {
                    @$transport->close();
                }
            }
        }
    }

    /**
     * Stops the server running. Kills the transport
     * and then stops the main serving loop.
     */
    public function stop()
    {
        $this->transport_->close();
        $this->stop_ = true;
    }
}
