<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage;

use Heptacom\HeptaConnect\Core\Storage\Contract\RequestStorageContract;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\RequestDeserializerInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\RequestSerializerInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestGet\FileReferenceGetRequestCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestPersist\FileReferencePersistRequestPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\FileReference\FileReferenceGetRequestActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\FileReference\FileReferencePersistRequestActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\FileReferenceRequestKeyCollection;
use Psr\Http\Message\RequestInterface;

final class RequestStorage extends RequestStorageContract
{
    private const DEFAULT_KEY = 'default';

    public function __construct(private RequestSerializerInterface $serializer, private RequestDeserializerInterface $deserializer, private FileReferenceGetRequestActionInterface $getRequestAction, private FileReferencePersistRequestActionInterface $persistRequestAction, private StorageKeyGeneratorContract $storageKeyGenerator)
    {
    }

    public function load(
        PortalNodeKeyInterface $portalNodeKey,
        FileReferenceRequestKeyInterface $fileReferenceRequestKey
    ): RequestInterface {
        $requestResults = $this->getRequestAction->getRequest(new FileReferenceGetRequestCriteria(
            $portalNodeKey,
            new FileReferenceRequestKeyCollection([$fileReferenceRequestKey])
        ));

        foreach ($requestResults as $requestResult) {
            try {
                return $this->deserializer->deserialize($requestResult->getSerializedRequest());
            } catch (\Throwable $throwable) {
                throw new \UnexpectedValueException(
                    'Denormalizing a serialized request failed: ' . $requestResult->getSerializedRequest(),
                    1647790094,
                    $throwable
                );
            }
        }

        throw new \RuntimeException(\sprintf(
            'Unable to find serialized request. FileReferenceRequestKey: %s; PortalNodeKey: %s (%s)',
            $this->storageKeyGenerator->serialize($fileReferenceRequestKey),
            $this->storageKeyGenerator->serialize($portalNodeKey->withAlias()),
            $this->storageKeyGenerator->serialize($portalNodeKey->withoutAlias())
        ), 1647791094);
    }

    public function persist(
        PortalNodeKeyInterface $portalNodeKey,
        RequestInterface $request
    ): FileReferenceRequestKeyInterface {
        $serializedRequest = $this->serializer->serialize($request);

        $payload = new FileReferencePersistRequestPayload($portalNodeKey);
        $payload->addSerializedRequest(self::DEFAULT_KEY, $serializedRequest);

        $fileReferenceRequestKey = $this->persistRequestAction
            ->persistRequest($payload)
            ->getFileReferenceRequestKey(self::DEFAULT_KEY);

        if (!$fileReferenceRequestKey instanceof FileReferenceRequestKeyInterface) {
            throw new \UnexpectedValueException(
                'Persisting serialized request failed: ' . $serializedRequest,
                1647791390
            );
        }

        return $fileReferenceRequestKey;
    }
}
