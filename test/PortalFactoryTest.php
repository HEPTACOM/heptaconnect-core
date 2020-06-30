<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test;

use Heptacom\HeptaConnect\Core\Portal\Exception\ClassNotFoundOnInstantionException;
use Heptacom\HeptaConnect\Core\Portal\Exception\InaccessableConstructorOnInstantionException;
use Heptacom\HeptaConnect\Core\Portal\Exception\UnexpectedClassInheritanceOnInstantionException;
use Heptacom\HeptaConnect\Core\Portal\Exception\UnexpectedRequiredParameterInConstructorOnInstantionException;
use Heptacom\HeptaConnect\Core\Portal\PortalFactory;
use Heptacom\HeptaConnect\Core\Test\Fixture\DependentPortal;
use Heptacom\HeptaConnect\Core\Test\Fixture\DependentPortalExtension;
use Heptacom\HeptaConnect\Core\Test\Fixture\UninstantiablePortal;
use Heptacom\HeptaConnect\Core\Test\Fixture\UninstantiablePortalExtension;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeExtensionInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Core\Portal\PortalFactory
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\AbstractInstantiationException
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\ClassNotFoundOnInstantionException
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\InaccessableConstructorOnInstantionException
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\UnexpectedClassInheritanceOnInstantionException
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\UnexpectedRequiredParameterInConstructorOnInstantionException
 */
class PortalFactoryTest extends TestCase
{
    public function testPortal(): void
    {
        $portalFactory = new PortalFactory();
        require_once __DIR__.'/Fixture/composer-integration/portal-package/src/Portal.php';

        static::assertInstanceOf(
            \HeptacomFixture\Portal\A\Portal::class,
            $portalFactory->instantiatePortalNode(\HeptacomFixture\Portal\A\Portal::class)
        );
    }

    public function testPortalExtension(): void
    {
        $portalFactory = new PortalFactory();
        require_once __DIR__.'/Fixture/composer-integration/portal-package-extension/src/PortalExtension.php';

        static::assertInstanceOf(
            \HeptacomFixture\Portal\Extension\PortalExtension::class,
            $portalFactory->instantiatePortalNodeExtension(\HeptacomFixture\Portal\Extension\PortalExtension::class)
        );
    }

    public function testFailingAtNonPortalClasses(): void
    {
        $portalFactory = new PortalFactory();

        try {
            $portalFactory->instantiatePortalNode(\DateTime::class);
        } catch (UnexpectedClassInheritanceOnInstantionException $exception) {
            static::assertEquals('DateTime', $exception->getClass());
            static::assertEquals(PortalNodeInterface::class, $exception->getExpectedInheritedClass());

            static::assertEquals(0, $exception->getCode());
            static::assertNull($exception->getPrevious());
            static::assertStringContainsString('Could not instantiate object', $exception->getMessage());
        }
    }

    public function testFailingAtNonPortalExtensionClasses(): void
    {
        $portalFactory = new PortalFactory();

        try {
            $portalFactory->instantiatePortalNodeExtension(\DateTime::class);
        } catch (UnexpectedClassInheritanceOnInstantionException $exception) {
            static::assertEquals('DateTime', $exception->getClass());
            static::assertEquals(PortalNodeExtensionInterface::class, $exception->getExpectedInheritedClass());

            static::assertEquals(0, $exception->getCode());
            static::assertNull($exception->getPrevious());
            static::assertStringContainsString('Could not instantiate object', $exception->getMessage());
        }
    }

    public function testFailingAtNonExistingPortalClasses(): void
    {
        $portalFactory = new PortalFactory();

        try {
            $portalFactory->instantiatePortalNode('Unknown🙃Class');
        } catch (ClassNotFoundOnInstantionException $exception) {
            static::assertEquals('Unknown🙃Class', $exception->getClass());

            static::assertEquals(0, $exception->getCode());
            static::assertNull($exception->getPrevious());
            static::assertStringContainsString('Could not instantiate object', $exception->getMessage());
        }
    }

    public function testFailingAtNonExistingPortalExtensionClasses(): void
    {
        $portalFactory = new PortalFactory();

        try {
            $portalFactory->instantiatePortalNodeExtension('Unknown🙃Class');
        } catch (ClassNotFoundOnInstantionException $exception) {
            static::assertEquals('Unknown🙃Class', $exception->getClass());

            static::assertEquals(0, $exception->getCode());
            static::assertNull($exception->getPrevious());
            static::assertStringContainsString('Could not instantiate object', $exception->getMessage());
        }
    }

    public function testFailingAtNonInstantiablePortalClasses(): void
    {
        $portalFactory = new PortalFactory();

        try {
            $portalFactory->instantiatePortalNode(UninstantiablePortal::class);
        } catch (InaccessableConstructorOnInstantionException $exception) {
            static::assertEquals(UninstantiablePortal::class, $exception->getClass());

            static::assertEquals(0, $exception->getCode());
            static::assertNull($exception->getPrevious());
            static::assertStringContainsString('Could not instantiate object', $exception->getMessage());
        }
    }

    public function testFailingAtNonInstantiablePortalExtensionClasses(): void
    {
        $portalFactory = new PortalFactory();

        try {
            $portalFactory->instantiatePortalNodeExtension(UninstantiablePortalExtension::class);
        } catch (InaccessableConstructorOnInstantionException $exception) {
            static::assertEquals(UninstantiablePortalExtension::class, $exception->getClass());

            static::assertEquals(0, $exception->getCode());
            static::assertNull($exception->getPrevious());
            static::assertStringContainsString('Could not instantiate object', $exception->getMessage());
        }
    }

    public function testFailingAtDependentPortalClasses(): void
    {
        $portalFactory = new PortalFactory();

        try {
            $portalFactory->instantiatePortalNode(DependentPortal::class);
        } catch (UnexpectedRequiredParameterInConstructorOnInstantionException $exception) {
            static::assertEquals(DependentPortal::class, $exception->getClass());

            static::assertEquals(0, $exception->getCode());
            static::assertNull($exception->getPrevious());
            static::assertStringContainsString('Could not instantiate object', $exception->getMessage());
        }
    }

    public function testFailingAtDependentPortalExtensionClasses(): void
    {
        $portalFactory = new PortalFactory();

        try {
            $portalFactory->instantiatePortalNodeExtension(DependentPortalExtension::class);
        } catch (UnexpectedRequiredParameterInConstructorOnInstantionException $exception) {
            static::assertEquals(DependentPortalExtension::class, $exception->getClass());

            static::assertEquals(0, $exception->getCode());
            static::assertNull($exception->getPrevious());
            static::assertStringContainsString('Could not instantiate object', $exception->getMessage());
        }
    }
}
