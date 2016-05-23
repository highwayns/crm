<?php

namespace OroCRM\Bundle\ChannelBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use OroCRM\Bundle\ChannelBundle\Async\Topics;
use OroCRM\Bundle\ChannelBundle\EventListener\TimezoneChangeListener;

class TimezoneChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testWasNotChanged()
    {
        $messageProducerMock = $this->createMessageProducerMock();
        $messageProducerMock
            ->expects($this->never())
            ->method('send')
        ;
        
        $listener = new TimezoneChangeListener($messageProducerMock);
        
        $listener->onConfigUpdate(new ConfigUpdateEvent([]));
    }

    public function testSuccessChange()
    {
        $messageProducerMock = $this->createMessageProducerMock();
        $messageProducerMock
            ->expects($this->once())
            ->method('send')
            ->with(Topics::AGGREGATE_LIFETIME_AVERAGE, ['force' => true], MessagePriority::VERY_LOW)
        ;

        $listener = new TimezoneChangeListener($messageProducerMock);

        $listener->onConfigUpdate(new ConfigUpdateEvent(['oro_locale.timezone' => ['old' => 1, 'new' => 2]]));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }
}
