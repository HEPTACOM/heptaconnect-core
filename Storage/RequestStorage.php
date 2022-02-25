<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage;

use Heptacom\HeptaConnect\Core\Storage\Normalizer\Psr7RequestDenormalizer;
use Heptacom\HeptaConnect\Core\Storage\Normalizer\Psr7RequestNormalizer;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestPersist\FileReferencePersistRequestPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\FileReference\FileReferencePersistRequestActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Psr\Http\Message\RequestInterface;

class RequestStorage
{
    private const DEFAULT_KEY = 'default';

    private Psr7RequestNormalizer $normalizer;

    private Psr7RequestDenormalizer $denormalizer;

    private FileReferencePersistRequestActionInterface $persistRequestAction;

    public function __construct(
        Psr7RequestNormalizer $normalizer,
        Psr7RequestDenormalizer $denormalizer,
        FileReferencePersistRequestActionInterface $persistRequestAction
    ) {
        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
        $this->persistRequestAction = $persistRequestAction;
    }

    public function load(
        PortalNodeKeyInterface $portalNodeKey,
        FileReferenceRequestKeyInterface $fileReferenceRequestKey
    ): ?RequestInterface {
        // TODO: implement me
        return null;
    }

    public function persist(
        PortalNodeKeyInterface $portalNodeKey,
        RequestInterface $request
    ): FileReferenceRequestKeyInterface {
        $serializedRequest = $this->normalizer->normalize($request);

        $payload = new FileReferencePersistRequestPayload($portalNodeKey);
        $payload->addSerializedRequest(self::DEFAULT_KEY, $serializedRequest);

        $fileReferenceRequestKey = $this->persistRequestAction
            ->persistRequest($payload)
            ->getFileReferenceRequestKey(self::DEFAULT_KEY);

        if (!$fileReferenceRequestKey instanceof FileReferenceRequestKeyInterface) {
            throw new \Exception();
        }

        return $fileReferenceRequestKey;
    }
}
