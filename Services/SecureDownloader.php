<?php

namespace Screamz\SecureDownloadBundle\Services;

use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequest;
use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequestError;
use Screamz\SecureDownloadBundle\Core\Classes\ErrorCode;
use Screamz\SecureDownloadBundle\Core\Classes\ResourceDownloadRequest;
use Screamz\SecureDownloadBundle\Core\Exceptions\DownloadRequestException;
use Stash\Invalidation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
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
     * Create a pre-authorization for a document with a given pass in the system to be downloaded further using two steps validation.
     *
     * @param string $filePath        Full path to the document file.
     *                                Check {@link SecureDownloaded::preAuthorizeResource} if you want to secure access to a resource instead
     * @param string $accessKey       A string key that will be used to verify your authorization on retrieving.
     * @param int    $documentHashTTL A TTL after when the authorization will expires (in ms), if ommited use the one in settings.
     *
     * @return string The transaction identifier.
     *
     * @throws DownloadRequestException
     */
    public function preAuthorizeDocumentPath($filePath, $accessKey, $documentHashTTL = null)
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

        $documentHash = $futureDownloadRequest->generateTransactionIdentifier($this->documentHashSalt);

        $cacheItem = $this->stash->getItem($this->stashPrefixKey.'/'.$documentHash);
        $transactionSucceed = $cacheItem->set($futureDownloadRequest, $documentHashTTL);

        if (!$transactionSucceed) {
            $futureDownloadRequest->addError(new DownloadRequestError('Unable to set item in stash pool'));
            throw new DownloadRequestException($futureDownloadRequest);
        }

        return $documentHash;
    }

    /**
     * Register a resource (ie: binary stream or anything that require a two step validation)
     *
     * @param string $resourceData       The resource data, can be the resource itself or any data that will intend to access the resource. You can use json_encode to get a string.
     * @param string $accessKey          A key (hash is nice) that is compared to the one used set when the document hash has been generated.
     *                                   You can also use current user unique data.
     * @param int    $resourceHashTTL    A TTL after when the authorization will expires (in ms), if ommited use the one in settings.
     *
     * @return string The transaction identifier.
     *
     * @throws DownloadRequestException
     */
    public function preAuthorizeResource($resourceData, $accessKey, $resourceHashTTL = null)
    {
        // Set default cache TTL from config if not specified
        $resourceHashTTL = $resourceHashTTL ?: $this->defaultTTL;
        $futureDownloadRequest = new ResourceDownloadRequest($resourceData, $accessKey);

        if (!$futureDownloadRequest->isProcessable()) {
            throw new DownloadRequestException($futureDownloadRequest);
        }

        $documentHash = $futureDownloadRequest->generateTransactionIdentifier($this->documentHashSalt);

        $cacheItem = $this->stash->getItem($this->stashPrefixKey.'/'.$documentHash);
        $transactionSucceed = $cacheItem->set($futureDownloadRequest, $resourceHashTTL);

        if (!$transactionSucceed) {
            $futureDownloadRequest->addError(new DownloadRequestError('Unable to set item in stash pool'));
            throw new DownloadRequestException($futureDownloadRequest);
        }

        return $documentHash;
    }

    /**
     * Mark the download request as stale, another call using this hash will make the request miss.
     *
     * @param string $transactionIdentifier The resource identifier or the document hash.
     * @param string $accessKey             The access key to check the authorization.
     *
     * @return bool If the transaction succeed.
     *
     * @throws DownloadRequestException
     */
    public function invalidateTransaction($transactionIdentifier, $accessKey)
    {
        $downloadRequest = $this->retrieveDownloadRequest($transactionIdentifier, $accessKey);
        if (!$downloadRequest->isProcessable()) {
            throw new DownloadRequestException($downloadRequest);
        }

        $downloadRequest = $this->stash->getItem($this->stashPrefixKey.'/'.$downloadRequest->getTransactionID());

        return $downloadRequest->clear();
    }

    /**
     * Try to retrieve a download request, will search for given transaction identifier and check the access key authorization.
     *
     * @param string $transactionIdentifier The transactionIdentifier generated using {@link SecureDownloader::preAuthorizeDocumentPath()}
     * @param string $accessKey             The access key to compare with the one provided on the transaction creation.
     *
     * @return DownloadRequest|ResourceDownloadRequest
     */
    private function retrieveDownloadRequest($transactionIdentifier, $accessKey)
    {
        $cacheItem = $this->stash->getItem($this->stashPrefixKey.'/'.$transactionIdentifier);

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
     * Return a {@link BinaryFileResponse} filled with the given transaction ID data.
     *
     * @param string $transactionIdentifier  The transactionIdentifier generated using {@link SecureDownloader::preAuthorizeDocumentPath} or {@link SecureDownloader::preAuthorizeResource}.
     * @param string $accessKey              The access key to compare with the one provided on the transaction creation.
     * @param string $contentDispositionType Use one constant of {@link ResponseHeaderBag}
     *
     * @return BinaryFileResponse
     * @throws DownloadRequestException
     * @see SecureDownloader::preAuthorizeDocumentPath()
     */
    public function getResourceBinaryFileResponse($transactionIdentifier, $accessKey, $contentDispositionType = ResponseHeaderBag::DISPOSITION_ATTACHMENT)
    {
        $downloadRequest = $this->retrieveDownloadRequest($transactionIdentifier, $accessKey);

        if (!$downloadRequest->isProcessable()) {
            throw new DownloadRequestException($downloadRequest);
        }

        // TODO : Check public false to see if there is a way to enchance this using Last-Modified or Etags
        $binaryResponse = new BinaryFileResponse($downloadRequest->getTransactionSavedData(), Response::HTTP_OK, [], false);
        $binaryResponse->headers->set('Content-Type', $downloadRequest->getMimeType());
        $binaryResponse->setContentDisposition(
            $contentDispositionType,
            $downloadRequest->getResourceName(),
            iconv('UTF-8', 'ASCII//TRANSLIT', $downloadRequest->getResourceName())
        );

        return $binaryResponse;
    }

    /**
     * Return A {@link Response} containing the base64 string of the given transaction ID data.
     *
     * This is useful if you want to display the image as an inline base64 <img> tag.
     *
     * @param string $transactionIdentifier  The transactionIdentifier generated using {@link SecureDownloader::preAuthorizeDocumentPath} or
     *                                      {@link SecureDownloader::preAuthorizeResource}.
     * @param string $accessKey              The access key to compare with the one provided on the transaction creation.
     * @param string $contentDispositionType Use one constant of {@link ResponseHeaderBag}
     *
     * @return Response A response with a base64 string representating the content of the stored resource.
     *
     * @throws DownloadRequestException
     */
    public function getResourceBase64ResponseString($transactionIdentifier, $accessKey, $contentDispositionType = ResponseHeaderBag::DISPOSITION_ATTACHMENT)
    {
        $downloadRequest = $this->retrieveDownloadRequest($transactionIdentifier, $accessKey);

        if (!$downloadRequest->isProcessable()) {
            throw new DownloadRequestException($downloadRequest);
        }

        $base64Response = new Response(
            $downloadRequest->getTransactionSavedData(), Response::HTTP_OK, [
                'Content-Type' => $downloadRequest->getMimeType(),
                'Content-Disposition' => $contentDispositionType."; filename=\"{$downloadRequest->getResourceName()}\"",
            ]
        );

        return $base64Response;
    }

    /**
     * Check if the resource is authorized to be accessed and return it.
     * Use this if you have just saved something in cache and you need to make another call in order to access the resource.
     *
     * @param string $transactionIdentifier The transactionIdentifier generated using {@link SecureDownloader::preAuthorizeDocumentPath} or
     *                                      {@link SecureDownloader::preAuthorizeResource}.
     * @param string $accessKey             The access key to compare with the one provided on the transaction creation.
     *
     * @return DownloadRequest|ResourceDownloadRequest
     *
     * @throws DownloadRequestException If the authorization failed and the resource is not accessible.
     */
    public function getResource($transactionIdentifier, $accessKey)
    {
        $downloadRequest = $this->retrieveDownloadRequest($transactionIdentifier, $accessKey);

        if (!$downloadRequest->isProcessable()) {
            throw new DownloadRequestException($downloadRequest);
        }

        return $downloadRequest;
    }
}
