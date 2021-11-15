<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStack;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class HttpHandlerStackBuilder implements HttpHandlerStackBuilderInterface
{
    private HttpHandlerCollection $sources;

    private HttpHandlerCollection $decorators;

    private string $path;

    private LoggerInterface $logger;

    /**
     * @var HttpHandlerContract[]
     */
    private array $selection = [];

    public function __construct(
        HttpHandlerCollection $sources,
        HttpHandlerCollection $decorators,
        string $path,
        LoggerInterface $logger
    ) {
        $this->sources = $sources;
        $this->decorators = $decorators;
        $this->path = $path;
        $this->logger = $logger;
    }

    public function push(HttpHandlerContract $httpHandler): self
    {
        if ($this->path === $httpHandler->getPath()) {
            $this->logger->debug(\sprintf(
                'HttpHandlerStackBuilder: Pushed %s as arbitrary http handler.',
                \get_class($httpHandler)
            ));

            $this->selection[] = $httpHandler;
        } else {
            $this->logger->debug(\sprintf(
                'HttpHandlerStackBuilder: Tried to push %s as arbitrary http handler, but it does not support path %s.',
                \get_class($httpHandler),
                $this->path,
            ));
        }

        return $this;
    }

    public function pushSource(): self
    {
        $last = null;

        foreach ($this->sources->bySupport($this->path) as $item) {
            $last = $item;
        }

        if ($last instanceof HttpHandlerContract) {
            $this->logger->debug(\sprintf(
                'HttpHandlerStackBuilder: Pushed %s as source http handler.',
                \get_class($last)
            ));

            if (!\in_array($last, $this->selection, true)) {
                $this->selection[] = $last;
            }
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->decorators->bySupport($this->path) as $item) {
            $this->logger->debug(\sprintf(
                'HttpHandlerStackBuilder: Pushed %s as decorator http handler.',
                \get_class($item)
            ));

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

        if ($stack instanceof LoggerAwareInterface) {
            $stack->setLogger($this->logger);
        }

        $this->logger->debug('HttpHandlerStackBuilder: Built emitter stack.');

        return $stack;
    }

    public function isEmpty(): bool
    {
        return empty($this->selection);
    }
}