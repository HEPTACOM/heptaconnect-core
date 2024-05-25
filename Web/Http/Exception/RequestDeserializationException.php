<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Exception;

class RequestDeserializationException extends \RuntimeException
{
    public function __construct(
        private readonly string $requestData,
        int $code,
        ?\Throwable $previous = null
    ) {
        parent::__construct('Deserialization of request data failed', $code, $previous);
    }

    public function getRequestData(): string
    {
        return $this->requestData;
    }
}
