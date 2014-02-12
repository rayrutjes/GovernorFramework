<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Gateway;

use Governor\Framework\CommandHandling\GenericCommandMessage;

/**
 * Description of DefaultCommandGatewayTest
 *
 * @author david
 */
class DefaultCommandGatewayTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $mockCommandBus;

    //private CommandDispatchInterceptor mockCommandMessageTransformer;


    public function setUp()
    {
        $this->mockCommandBus = $this->getMock('Governor\Framework\CommandHandling\CommandBusInterface');
        $this->testSubject = new DefaultCommandGateway($this->mockCommandBus);
    }

    public function testSend()
    {
        $command = new HelloCommand('Hi !!!');
        
        $this->mockCommandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->anything());
        
        $this->testSubject->send($command);
    }

}

class HelloCommand
{

    private $message;

    function __construct($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

}