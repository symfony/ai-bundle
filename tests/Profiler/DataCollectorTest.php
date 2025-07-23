<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\AIBundle\Tests\Profiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\Toolbox\ToolboxInterface;
use Symfony\AI\AIBundle\Profiler\DataCollector;
use Symfony\AI\AIBundle\Profiler\TraceablePlatform;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\ResultPromise;
use Symfony\AI\Platform\Result\StreamResult;
use Symfony\AI\Platform\Result\TextResult;

#[CoversClass(DataCollector::class)]
#[UsesClass(TraceablePlatform::class)]
#[UsesClass(ResultPromise::class)]
class DataCollectorTest extends TestCase
{
    #[Test]
    public function collectsDataForNonStreamingResponse()
    {
        $platform = $this->createMock(PlatformInterface::class);
        $traceablePlatform = new TraceablePlatform($platform);
        $messageBag = new MessageBag(Message::ofUser(new Text('Hello')));
        $result = new TextResult('Assistant response');

        $platform->method('invoke')->willReturn(new ResultPromise(static fn () => $result, $this->createStub(RawResultInterface::class)));

        $result = $traceablePlatform->invoke($this->createStub(Model::class), $messageBag, ['stream' => false]);
        $this->assertSame('Assistant response', $result->asText());

        $dataCollector = new DataCollector([$traceablePlatform], $this->createStub(ToolboxInterface::class), []);
        $dataCollector->lateCollect();

        $this->assertCount(1, $dataCollector->getPlatformCalls());
        $this->assertSame('Assistant response', $dataCollector->getPlatformCalls()[0]['result']);
    }

    #[Test]
    public function collectsDataForStreamingResponse()
    {
        $platform = $this->createMock(PlatformInterface::class);
        $traceablePlatform = new TraceablePlatform($platform);
        $messageBag = new MessageBag(Message::ofUser(new Text('Hello')));
        $result = new StreamResult(
            (function () {
                yield 'Assistant ';
                yield 'response';
            })(),
        );

        $platform->method('invoke')->willReturn(new ResultPromise(static fn () => $result, $this->createStub(RawResultInterface::class)));

        $result = $traceablePlatform->invoke($this->createStub(Model::class), $messageBag, ['stream' => true]);
        $this->assertSame('Assistant response', implode('', iterator_to_array($result->asStream())));

        $dataCollector = new DataCollector([$traceablePlatform], $this->createStub(ToolboxInterface::class), []);
        $dataCollector->lateCollect();

        $this->assertCount(1, $dataCollector->getPlatformCalls());
        $this->assertSame('Assistant response', $dataCollector->getPlatformCalls()[0]['result']);
    }
}
