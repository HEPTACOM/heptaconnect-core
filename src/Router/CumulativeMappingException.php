<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Router;

use Throwable;

class CumulativeMappingException extends \Exception
{
    /**
     * @var Throwable[]
     */
    private array $exceptions;

    public function __construct(string $message, \Throwable ...$exceptions)
    {
        $this->exceptions = $exceptions;
        parent::__construct($message);
    }
}
