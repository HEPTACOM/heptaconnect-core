<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Psr\Http\Message\RequestInterface;

class RequestFileReference extends FileReferenceContract
{
    private RequestInterface $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }
}
