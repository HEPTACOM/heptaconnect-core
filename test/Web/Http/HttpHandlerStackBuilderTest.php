<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Web\Http;

use Heptacom\HeptaConnect\Core\Web\Http\HttpHandlerStackBuilder;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Web\Http\HttpHandlerStackBuilder
 */
class HttpHandlerStackBuilderTest extends TestCase
{
    public function testStackBuilderManualOrder(): void
    {
        $stackBuilder = new HttpHandlerStackBuilder(
            new HttpHandlerCollection(),
            new HttpHandlerCollection(),
            'foobar',
            $this->createMock(LoggerInterface::class),
        );

        $calc = [];

        $handler1 = $this->createMock(HttpHandlerContract::class);
        $handler1->method('handle')
            ->willReturnCallback(
                static function (
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    HttpHandleContextInterface $context,
                    HttpHandlerStackInterface $stack
                ) use (&$calc) {
                    $calc[] = 1;

                    return $stack->next($request, $response, $context);
                }
            );
        $handler1->method('supports')->willReturn('foobar');

        $handler2 = $this->createMock(HttpHandlerContract::class);
        $handler2->method('handle')
            ->willReturnCallback(
                static function (
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    HttpHandleContextInterface $context,
                    HttpHandlerStackInterface $stack
                ) use (&$calc) {
                    $calc[] = 2;

                    return $stack->next($request, $response, $context);
                }
            );
        $handler2->method('supports')->willReturn('foobar');
        $stackBuilder->push($handler1); // resembles source
        $stackBuilder->push($handler2); // resembles decorators
        $stack = $stackBuilder->build();
        $stack->next(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(ResponseInterface::class),
            $this->createMock(HttpHandleContextInterface::class)
        );

        self::assertEquals([2, 1], $calc);
    }

    public function testStackBuilderOrderFromCtor(): void
    {
        $calc = [];

        $handler1 = $this->createMock(HttpHandlerContract::class);
        $handler1->method('handle')
            ->willReturnCallback(
                static function (
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    HttpHandleContextInterface $context,
                    HttpHandlerStackInterface $stack
                ) use (&$calc) {
                    $calc[] = 1;

                    return $stack->next($request, $response, $context);
                }
            );
        $handler1->method('supports')->willReturn('foobar');

        $handler2 = $this->createMock(HttpHandlerContract::class);
        $handler2->method('handle')
            ->willReturnCallback(
                static function (
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    HttpHandleContextInterface $context,
                    HttpHandlerStackInterface $stack
                ) use (&$calc) {
                    $calc[] = 2;

                    return $stack->next($request, $response, $context);
                }
            );
        $handler2->method('supports')->willReturn('foobar');

        $stackBuilder = new HttpHandlerStackBuilder(
            new HttpHandlerCollection([$handler1, $handler2]),
            new HttpHandlerCollection([$handler2]),
            'foobar',
            $this->createMock(LoggerInterface::class),
        );

        $stackBuilder->pushSource();
        $stackBuilder->pushDecorators();
        $stack = $stackBuilder->build();

        $stack->next(
            $this->createMock(ServerRequestInterface::class),
            $this->createMock(ResponseInterface::class),
            $this->createMock(HttpHandleContextInterface::class)
        );

        self::assertEquals([2, 1], $calc);
    }
}
