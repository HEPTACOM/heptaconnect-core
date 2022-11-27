<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Exception;

use Psr\Http\Message\RequestInterface;

class RequestSerializationException extends \RuntimeException
{
    private RequestInterface $request;

    public function __construct(RequestInterface $request, int $code, ?\Throwable $previous = null)
    {
        parent::__construct('Serialization of request failed', $code, $previous);
        $this->request = $request;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
