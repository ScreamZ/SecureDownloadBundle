<?php

namespace Screamz\SecureDownloadBundle\Services;

use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequest;
use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequestError;
use Screamz\SecureDownloadBundle\Core\Classes\ErrorCode;
use Screamz\SecureDownloadBundle\Core\Classes\ResourceDownloadRequest;
use Screamz\SecureDownloadBundle\Core\Classes\Response\Base64BinaryFileResponse;
use Screamz\SecureDownloadBundle\Core\Exceptions\DownloadRequestException;
use Stash\Invalidation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Tedivm\StashBundle\Service\CacheService;

/**
 * Class SecureDownloader
 *
 * This is the main service allowing you to encrypt your document as hash that can be fetched further.
 *
 * @author Andréas HANSS <ahanss@kaliop.com>
 */
class SecureDownloader
{
    private $stash;
    private $stashPrefixKey;
    private $documentHashSalt;
    private $defaultTTL;

    /**
     * SecureDownloader constructor.
     *
     * @param CacheService $cacheService
     * @param array        $config
     */
    public function __construct(CacheService $cacheService, array $config)
    {
        $this->stash = $cacheService;
        $this->stashPrefixKey = $config['cache']['stash_prefix_key'];
        $this->defaultTTL = $config['cache']['default_ttl'];
        $this->documentHashSalt = $config['document']['hash_salt'];

    }

    /**
     * Generate an unique Hash that will be used to query download later.
     *
     * @param string $filePath        full path to the document
     * @param string $accessKey       A key (hash is nice) that will be required on
     *                                {@link SecureDownloader::initiateDownloadRequest() }
     * @param int    $documentHashTTL Optionnal : HASH TTL in seconds, if null it will use the default value defined in
     *                                config)
     *
     * @return string The hash that will be used by {@link SecureDownloader::initiateDownloadRequest()} to fetch the
     *                document.
     * @throws DownloadRequestException
     *
     * @see SecureDownloader::initiateDownloadRequest()
     */
    public function generateHash($filePath, $accessKey, $documentHashTTL = null)
    {
        // Set default cache TTL from config if not specified
        $documentHashTTL = $documentHashTTL ?: $this->defaultTTL;

        // Sanitize string (folder path) and check path
        $filePath = preg_replace('%/{2,}%', '/', $filePath);
        $futureDownloadRequest = new DownloadRequest($filePath, $accessKey);

        // Check if the request is processable (filepath exists, ...)
        if (!$futureDownloadRequest->isProcessable()) {
            throw new DownloadRequestException($futureDownloadRequest);
        }

        $documentHash = $futureDownloadRequest->generateRequestHash($this->documentHashSalt);

        $cacheItem = $this->stash->getItem($this->stashPrefixKey . '/' . $documentHash);
        $transactionSucceed = $cacheItem->set($futureDownloadRequest, $documentHashTTL);

        if (!$transactionSucceed) {
            $futureDownloadRequest->addError(new DownloadRequestError('Unable to set item in stash pool'));
            throw new DownloadRequestException($futureDownloadRequest);
        }

        return $documentHash;
    }

    /**
     * Mark the download request as stale, another call using this hash will make the request miss.
     *
     * @param string $documentHash
     * @param string $accessKey
     *
     * @return bool
     * @throws DownloadRequestException
     */
    public function invalidate($documentHash, $accessKey)
    {
        $downloadRequest = $this->initiateDownloadRequest($documentHash, $accessKey);
        if (!$downloadRequest->isProcessable()) {
            throw new DownloadRequestException($downloadRequest);
        }

        $downloadRequest = $this->stash->getItem($this->stashPrefixKey . '/' . $downloadRequest->getHash());

        return $downloadRequest->clear();
    }

    /**
     * Attempt to download the document matching the given path.
     *
     * @param string $documentHash The documentHash generated using {@link SecureDownloader::generateHash()}
     * @param string $accessKey    A key (hash is nice) that is compared to the one used set when the document hash has
     *                             been generated.
     *
     * @return BinaryFileResponse
     * @throws DownloadRequestException
     *
     * @see SecureDownloader::generateHash()
     */
    public function downloadHash($documentHash, $accessKey)
    {
        $downloadRequest = $this->initiateDownloadRequest($documentHash, $accessKey);

        if (!$downloadRequest->isProcessable()) {
            throw new DownloadRequestException($downloadRequest);
        }

        $binaryResponse = new BinaryFileResponse($downloadRequest->getRequestSavedData(), 200, array(), false);
        $binaryResponse->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($downloadRequest->getRequestSavedData()),
            iconv('UTF-8', 'ASCII//TRANSLIT', basename($downloadRequest->getRequestSavedData()))
        );

        return $binaryResponse;
    }

    /**
     * Attempt to encode the file as a base64 data hash, used for multiple purpose like rendering it from template or
     * send image through web service.
     *
     * @param string $documentHash
     * @param string $accessKey
     *
     * @return Base64BinaryFileResponse A response with a base64 content of the document stored.
     *
     * @throws DownloadRequestException
     */
    public function getBase64Blob($documentHash, $accessKey)
    {
        $downloadRequest = $this->initiateDownloadRequest($documentHash, $accessKey);

        if (!$downloadRequest->isProcessable()) {
            throw new DownloadRequestException($downloadRequest);
        }

        return new Base64BinaryFileResponse($downloadRequest->getRequestSavedData(), 200);
    }

    /**
     * Initiate the download request, create a new instance with given parameters.
     *
     * @param string $documentHash The documentHash generated using {@link SecureDownloader::generateHash()}
     * @param string $accessKey    A key (hash is nice) that is compared to the one used set when the document hash has
     *                             been generated.
     *
     * @return DownloadRequest|ResourceDownloadRequest
     */
    private function initiateDownloadRequest($documentHash, $accessKey)
    {
        $cacheItem = $this->stash->getItem($this->stashPrefixKey . '/' . $documentHash);

        $downloadRequest = $cacheItem->get(Invalidation::NONE);

        if ($cacheItem->isMiss()) {
            // Delete stash item
            $cacheItem->clear();

            // Mark downloadRequest as expired (documentHash expired)
            $downloadRequest = new DownloadRequest();
            $downloadRequest->addError(new DownloadRequestError(ErrorCode::DOCUMENT_EXPIRED, 'Document hash expired / missing.'));
            return $downloadRequest;
        }

        // Assume that we have a DownloadRequest object
        if (!($downloadRequest instanceof DownloadRequest)) {
            $downloadRequest = new DownloadRequest();
            $downloadRequest->addError(new DownloadRequestError(ErrorCode::INVALID_STORED_DOCUMENT_TYPE, 'Given Hash doesn\'t match a DownloadRequest object.'));
            return $downloadRequest;
        }

        /** @var DownloadRequest $downloadRequest Here we are sure it is */
        // Check the authorization to access the file
        if (!$downloadRequest->isAccessKeyValid($accessKey)) {
            $downloadRequest->addError(new DownloadRequestError(ErrorCode::INVALID_ACCESS_KEY, 'Invalid access key provided for given document hash.'));
            return $downloadRequest;
        }

        return $downloadRequest;
    }

    /**
     * @param string $resourceIdentifier An unique identifier that identify the resource in the whole system.
     * @param string $accessKey          A key (hash is nice) that is compared to the one used set when the document
     *                                   hash has been generated. You can also use current user unique data.
     * @param int    $resourceHashTTL    A TTL after when the authorization will expires(in ms).
     *
     * @return string The resource hash that will be required in order to check resource authorization further.
     *
     * @throws DownloadRequestException
     */
    public function preAuthorizeResource($resourceIdentifier, $accessKey, $resourceHashTTL = null)
    {
        // Set default cache TTL from config if not specified
        $resourceHashTTL = $resourceHashTTL ?: $this->defaultTTL;
        $futureDownloadRequest = new ResourceDownloadRequest($resourceIdentifier, $accessKey);

        if (!$futureDownloadRequest->isProcessable()) {
            throw new DownloadRequestException($futureDownloadRequest);
        }

        $documentHash = $futureDownloadRequest->generateRequestHash($this->documentHashSalt);

        $cacheItem = $this->stash->getItem($this->stashPrefixKey . '/' . $documentHash);
        $transactionSucceed = $cacheItem->set($futureDownloadRequest, $resourceHashTTL);

        if (!$transactionSucceed) {
            $futureDownloadRequest->addError(new DownloadRequestError('Unable to set item in stash pool'));
            throw new DownloadRequestException($futureDownloadRequest);
        }

        return $documentHash;
    }

    public function checkResourceAuthorization($resourceIdentifier, $accessKey)
    {
        return $this->initiateDownloadRequest($resourceIdentifier, $accessKey)->isProcessable();
    }
}
