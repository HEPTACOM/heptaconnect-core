<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Portal;

use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotFoundException
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotInstantiable
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotInstantiableEndlessLoopDetected
 * @covers \Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainer
 * @covers \Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder
 */
class PortalStackServiceContainerBuilderTest extends TestCase
{
    public function testServiceRetrieval(): void
    {
        $builder = new PortalStackServiceContainerBuilder();
        $container = $builder->build($this->getPortalContract(static fn (array $s): array => \array_merge($s, [
            'test_service' => new \stdClass(),
        ])), new PortalExtensionCollection([
            $this->getPortalExtensionContract(static fn (array $s): array => $s),
        ]));

        static::assertTrue($container->has('test_service'));
        static::assertInstanceOf(\stdClass::class, $container->get('test_service'));
    }

    public function testServiceDecoratedRetrieval(): void
    {
        $builder = new PortalStackServiceContainerBuilder();
        $container = $builder->build($this->getPortalContract(static fn (array $s): array => \array_merge($s, [
            'test_service' => (object) [
                'value' => 17,
            ],
        ])), new PortalExtensionCollection([
            $this->getPortalExtensionContract(static fn (array $s): array => \array_merge($s, [
                'test_service' => (object) [
                    'value' => $s['test_service']->value + 8,
                ],
            ])),
            $this->getPortalExtensionContract(static fn (array $s): array => \array_merge($s, [
                'test_service' => (object) [
                    'value' => $s['test_service']->value * 5,
                ],
            ])),
        ]));

        static::assertTrue($container->has('test_service'));

        $service = $container->get('test_service');

        static::assertInstanceOf(\stdClass::class, $service);
        static::assertEquals($service->value, 125);
    }

    protected function getPortalContract(callable $serviceToService): PortalContract
    {
        return new class($serviceToService) extends PortalContract {
            /**
             * @var callable
             */
            private $serviceToService;

            public function __construct(callable $serviceToService)
            {
                $this->serviceToService = $serviceToService;
            }

            public function getServices(): array
            {
                return \call_user_func($this->serviceToService, parent::getServices());
            }
        };
    }

    protected function getPortalExtensionContract(callable $serviceToService): PortalExtensionContract
    {
        return new class($serviceToService) extends PortalExtensionContract {
            /**
             * @var callable
             */
            private $serviceToService;

            public function __construct(callable $serviceToService)
            {
                $this->serviceToService = $serviceToService;
            }

            public function extendServices(array $services): array
            {
                return \call_user_func($this->serviceToService, $services);
            }

            public function supports(): string
            {
                return PortalContract::class;
            }
        };
    }
}
