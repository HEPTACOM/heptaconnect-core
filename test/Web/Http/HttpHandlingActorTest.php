<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Web\Http;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Web\Http\HttpHandlingActor;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStack;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Component\LogMessage
 * @covers \Heptacom\HeptaConnect\Core\Web\Http\HttpHandlingActor
 */
class HttpHandlingActorTest extends TestCase
{
    public function testActingFails(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())
            ->method('critical')
            ->with(LogMessage::WEB_HTTP_HANDLE_NO_THROW());

        $stack = new HttpHandlerStack([new class () extends HttpHandlerContract {
            protected function supports(): string
            {
                return 'foobar';
            }

            protected function run(
                ServerRequestInterface $request,
                ResponseInterface $response,
                HttpHandleContextInterface $context
            ): ResponseInterface {
                throw new \Exception('catch me if you can');
            }
        }]);

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('withStatus')->willReturnSelf();
        $actor = new HttpHandlingActor($logger);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(static fn (string $id) => [
            LoggerInterface::class => $logger,
        ][$id] ?? null);
        $context = $this->createMock(HttpHandleContextInterface::class);
        $context->method('getContainer')->willReturn($container);

        $actor->performHttpHandling($request, $response, $stack, $context);
    }
}
