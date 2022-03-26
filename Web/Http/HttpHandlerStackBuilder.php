<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStack;
use Psr\Log\LoggerInterface;

class HttpHandlerStackBuilder implements HttpHandlerStackBuilderInterface
{
    private ?HttpHandlerContract $source;

    private HttpHandlerCollection $decorators;

    private string $path;

    private LoggerInterface $logger;

    /**
     * @var HttpHandlerContract[]
     */
    private array $selection = [];

    public function __construct(
        HttpHandlerCollection $sources,
        string $path,
        LoggerInterface $logger
    ) {
        $sources = new HttpHandlerCollection($sources->bySupport($path));
        $this->source = $sources->shift();
        $this->decorators = $sources;
        $this->path = $path;
        $this->logger = $logger;
    }

    public function push(HttpHandlerContract $httpHandler): self
    {
        if ($this->path === $httpHandler->getPath()) {
            $this->logger->debug('HttpHandlerStackBuilder: Pushed an arbitrary http handler.', [
                'web_http_handler' => $httpHandler,
            ]);

            $this->selection[] = $httpHandler;
        } else {
            $this->logger->debug(
                \sprintf(
                    'HttpHandlerStackBuilder: Tried to push an arbitrary http handler, but it does not support path %s.',
                    $this->path,
                ),
                [
                    'web_http_handler' => $httpHandler,
                ]
            );
        }

        return $this;
    }

    public function pushSource(): self
    {
        if ($this->source instanceof HttpHandlerContract) {
            $this->logger->debug('HttpHandlerStackBuilder: Pushed the source http handler.', [
                'web_http_handler' => $this->source,
            ]);

            if (!\in_array($this->source, $this->selection, true)) {
                $this->selection[] = $this->source;
            }
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->decorators as $item) {
            $this->logger->debug('HttpHandlerStackBuilder: Pushed a decorator http handler.', [
                'web_http_handler' => $this->source,
            ]);

            if (!\in_array($item, $this->selection, true)) {
                $this->selection[] = $item;
            }
        }

        return $this;
    }

    public function build(): HttpHandlerStackInterface
    {
        $stack = new HttpHandlerStack(\array_map(
            static fn (HttpHandlerContract $e) => clone $e,
            \array_reverse($this->selection, false),
        ));
        $stack->setLogger($this->logger);

        $this->logger->debug('HttpHandlerStackBuilder: Built emitter stack.');

        return $stack;
    }

    public function isEmpty(): bool
    {
        return $this->selection === [];
    }
}
