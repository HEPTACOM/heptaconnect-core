<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage;

use Heptacom\HeptaConnect\Core\Storage\Contract\RequestStorageContract;
use Heptacom\HeptaConnect\Core\Storage\Normalizer\Psr7RequestDenormalizer;
use Heptacom\HeptaConnect\Core\Storage\Normalizer\Psr7RequestNormalizer;
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

    private Psr7RequestNormalizer $normalizer;

    private Psr7RequestDenormalizer $denormalizer;

    private FileReferenceGetRequestActionInterface $getRequestAction;

    private FileReferencePersistRequestActionInterface $persistRequestAction;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        Psr7RequestNormalizer $normalizer,
        Psr7RequestDenormalizer $denormalizer,
        FileReferenceGetRequestActionInterface $getRequestAction,
        FileReferencePersistRequestActionInterface $persistRequestAction,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
        $this->getRequestAction = $getRequestAction;
        $this->persistRequestAction = $persistRequestAction;
        $this->storageKeyGenerator = $storageKeyGenerator;
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
            if (
                !$requestResult->getPortalNodeKey()->equals($portalNodeKey)
                || !$requestResult->getRequestKey()->equals($fileReferenceRequestKey)
            ) {
                continue;
            }

            $request = $this->denormalizer->denormalize(
                $requestResult->getSerializedRequest(),
                'psr7-request'
            );

            if (!$request instanceof RequestInterface) {
                throw new \UnexpectedValueException(
                    'Denormalizing a serialized request failed: ' . $requestResult->getSerializedRequest(),
                    1647790094
                );
            }

            return $request;
        }

        throw new \RuntimeException(\sprintf(
            'Unable to find serialized request. FileReferenceRequestKey: %s; PortalNodeKey: %s',
            $this->storageKeyGenerator->serialize($fileReferenceRequestKey),
            $this->storageKeyGenerator->serialize($portalNodeKey)
        ), 1647791094);
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
            throw new \UnexpectedValueException(
                'Persisting serialized request failed: ' . $serializedRequest,
                1647791390
            );
        }

        return $fileReferenceRequestKey;
    }
}
