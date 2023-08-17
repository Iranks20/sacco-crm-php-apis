<?php

namespace TheCodeMill\FlashMessage\Tests;

use TheCodeMill\FlashMessage\Message;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf(
            Message::class,
            new Message('foobar', Message::STATUS_INFO)
        );
    }

    public function testSupportedStatuses()
    {
        $statusses = Message::statuses();

        $this->assertInternalType('array', $statusses);
        $this->assertNotEmpty($statusses);
    }

    public function testContent()
    {
        $content = 'foobar';
        $message = new Message($content, Message::STATUS_INFO);

        $this->assertEquals($message->content(), $content);
    }

    public function testStatuses()
    {
        $this->assertEquals(Message::info('foobar')->status(), Message::STATUS_INFO);
        $this->assertEquals(Message::success('foobar')->status(), Message::STATUS_SUCCESS);
        $this->assertEquals(Message::warning('foobar')->status(), Message::STATUS_WARNING);
        $this->assertEquals(Message::danger('foobar')->status(), Message::STATUS_DANGER);
    }
}
