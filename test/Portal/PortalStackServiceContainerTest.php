<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Portal;

use Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotFoundException;
use Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotInstantiable;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotFoundException
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotInstantiable
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotInstantiableEndlessLoopDetected
 * @covers \Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainer
 */
class PortalStackServiceContainerTest extends TestCase
{
    public function testServiceRetrieval(): void
    {
        $testService = new \stdClass();
        $container = new PortalStackServiceContainer([
            'test_service' => $testService,
        ]);

        static::assertTrue($container->has('test_service'));
        static::assertInstanceOf(\stdClass::class, $container->get('test_service'));
    }

    public function testServiceRetrievalByCallable(): void
    {
        $testService = new \stdClass();
        $container = new PortalStackServiceContainer([
            'test_service' => static fn (): \stdClass => $testService,
        ]);

        static::assertTrue($container->has('test_service'));
        static::assertInstanceOf(\stdClass::class, $container->get('test_service'));
    }

    public function testFailOnMissingService(): void
    {
        $container = new PortalStackServiceContainer([]);

        static::assertFalse($container->has('test_service'));

        $exception = null;

        try {
            $container->get('test_service');
            static::fail('There should have been an exception throw.');
        } catch (\Throwable $throwable) {
            $exception = $throwable;
        }

        static::assertInstanceOf(NotFoundExceptionInterface::class, $exception);
        static::assertInstanceOf(ServiceNotFoundException::class, $exception);
        static::assertStringContainsString('test_service', $exception->getMessage());
        /** @var $exception ServiceNotFoundException */
        static::assertEquals('test_service', $exception->getId());
    }

    public function testFailOnEndlessCallable(): void
    {
        $container = new PortalStackServiceContainer([
            'test_service' => $this->callableThatReturnsItselfAsReference(),
        ]);

        try {
            $container->get('test_service');
        } catch (\Throwable $throwable) {
            $exception = $throwable;
        }

        static::assertInstanceOf(ServiceNotInstantiable::class, $exception);
        static::assertInstanceOf(ContainerExceptionInterface::class, $exception);
        static::assertStringContainsString('test_service', $exception->getMessage());

        /** @var $exception ServiceNotInstantiable */
        static::assertEquals('test_service', $exception->getId());
    }

    /**
     * @dataProvider provideNonStringValues
     */
    public function testDoNotFindNonStringServiceId($id): void
    {
        $container = new PortalStackServiceContainer([]);

        static::assertFalse($container->has($id));

        $exception = null;

        try {
            $container->get($id);
        } catch (\Throwable $throwable) {
            $exception = $throwable;
        }

        static::assertInstanceOf(NotFoundExceptionInterface::class, $exception);
        static::assertInstanceOf(ServiceNotFoundException::class, $exception);
        /** @var $exception ServiceNotFoundException */
        static::assertEquals('', $exception->getId());
    }

    public function provideNonStringValues(): iterable
    {
        yield [17];
        yield [new \stdClass()];
        yield [new \DateTime()];
        yield [13.37];
        yield [null];
    }

    public function callableThatReturnsItselfAsReference(): callable
    {
        return [$this, 'callableThatReturnsItselfAsReference'];
    }
}
