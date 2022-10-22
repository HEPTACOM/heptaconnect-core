<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Exception;

class RequestDeserializationException extends \RuntimeException
{
    private string $requestData;

    public function __construct(string $requestData, int $code, ?\Throwable $previous = null)
    {
        parent::__construct('Deserialization of request data failed', $code, $previous);
        $this->requestData = $requestData;
    }

    public function getRequestData(): string
    {
        return $this->requestData;
    }
}
