<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Reception;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Reception\ReceiverStackBuilder;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Core\Reception\ReceiverStackBuilder
 */
class ReceiverStackBuilderTest extends TestCase
{
    public function testStackBuilderOrder(): void
    {
        $stackBuilder = new ReceiverStackBuilder(
            $this->createMock(PortalRegistryInterface::class),
            $this->createMock(PortalNodeKeyInterface::class),
            '',
        );

        $calc = [];

        $receiver1 = $this->createMock(ReceiverContract::class);
        $receiver1->method('receive')
            ->willReturnCallback(
                static function (MappedDatasetEntityCollection $m, ReceiveContextInterface $c, ReceiverStackInterface $s) use (&$calc): iterable {
                    $calc[] = 1;

                    return $s->next($m, $c);
                }
            );
        $receiver2 = $this->createMock(ReceiverContract::class);
        $receiver2->method('receive')
            ->willReturnCallback(
                static function (MappedDatasetEntityCollection $m, ReceiveContextInterface $c, ReceiverStackInterface $s) use (&$calc): iterable {
                    $calc[] = 2;

                    return $s->next($m, $c);
                }
            );
        $stackBuilder->push($receiver1); // resembles source
        $stackBuilder->push($receiver2); // resembles decorators
        $stack = $stackBuilder->build();
        $stack->next(new MappedDatasetEntityCollection(), $this->createMock(ReceiveContextInterface::class));

        self::assertEquals([2, 1], $calc);
    }
}
