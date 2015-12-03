<?php

namespace Screamz\SecureDownloadBundle\Services;

use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequest;
use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequestError;
use Screamz\SecureDownloadBundle\Core\Exceptions\DownloadRequestException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tedivm\StashBundle\Service\CacheService;
use Stash\Invalidation;

/**
 * Class SecureDownloader
 *
 * @author Andréas HANSS <ahanss@kaliop.com>
 */
class SecureDownloader
{
    const   DOCUMENT_HASH_TTL = 300; // 5 minuts
    private $stash;
    private $stashPrefixKey;
    private $documentHashSalt;

    /**
     * SecureDownloader constructor.
     *
     * @param CacheService $cacheService
     * @param array        $config
     */
    public function __construct(CacheService $cacheService, array $config)
    {
        $this->stash = $cacheService;
        $this->stashPrefixKey = $config['stashPrefixKey'];
        $this->documentHashSalt = $config['documentHashSalt'];

    }

    /**
     * Generate an unique Hash that will be used to query download later.
     *
     * @param string $filePath        full path to the document
     * @param string $accessKey       A key (hash is nice) that will be required on {@link SecureDownloader::createDownloadRequest() }
     * @param int    $documentHashTTL (Optionnal : HASH TTL in seconds)
     *
     * @return string The hash that will be used by {@link SecureDownloader::createDownloadRequest()} to fetch the document.
     * @throws DownloadRequestException
     *
     * @see SecureDownloader::createDownloadRequest()
     */
    public function generateHash($filePath, $accessKey, $documentHashTTL = self::DOCUMENT_HASH_TTL)
    {
        $futureDownloadRequest = new DownloadRequest($filePath, $accessKey);

        // Sanitize string (folder path)
        $filePath = preg_filter('%/{2,}%', '/', $filePath);
        $documentHash = md5($this->documentHashSalt.$filePath);

        $cacheItem = $this->stash->getItem($this->stashPrefixKey.'/'.$documentHash);
        $transactionSucceed = $cacheItem->set($futureDownloadRequest, $documentHashTTL);

        if (!$transactionSucceed) {
            $futureDownloadRequest->addError(new DownloadRequestError('Unable to set item in stash pool'));
            throw new DownloadRequestException($futureDownloadRequest);
        }

        return $documentHash;
    }

    /**
     * Attempt to download the document matching the given path.
     *
     * @param string $documentHash The documentHash generated using {@link SecureDownloader::generateHash()}
     * @param string $accessKey    A key (hash is nice) that is compared to the one used set when the document hash has been generated.
     *
     * @return BinaryFileResponse
     * @throws DownloadRequestException
     *
     * @see SecureDownloader::generateHash()
     */
    public function downloadHash($documentHash, $accessKey)
    {
        $downloadRequest = $this->createDownloadRequest($documentHash, $accessKey);

        if ($downloadRequest->isProcessable()) {
            return new BinaryFileResponse($downloadRequest->getFilePath());
        } else {
            throw new DownloadRequestException($downloadRequest);
        }
    }

    /**
     * Instantiate a new download request.
     *
     * @param string $documentHash The documentHash generated using {@link SecureDownloader::generateHash()}
     * @param string $accessKey    A key (hash is nice) that is compared to the one used set when the document hash has been generated.
     *
     * @return DownloadRequest
     */
    private function createDownloadRequest($documentHash, $accessKey)
    {
        $cacheItem = $this->stash->getItem($this->stashPrefixKey.'/'.$documentHash);

        // A voir la méthode d'invalidation
        $downloadRequest = $cacheItem->get(Invalidation::NONE);

        if ($cacheItem->isMiss()) {
            // Delete stash item
            $cacheItem->clear();

            // Mark downloadRequest as expired (documentHash expired)
            $downloadRequest = new DownloadRequest();
            $downloadRequest->addError(new DownloadRequestError('Document hash expired / missing.'));
            return $downloadRequest;
        }

        // Assume that we have a DownloadRequest object
        if (!($downloadRequest instanceof DownloadRequest)) {
            $downloadRequest = new DownloadRequest();
            $downloadRequest->addError(new DownloadRequestError('Given Hash doesn\'t match a DownloadRequest object.'));
            return $downloadRequest;
        }

        /** @var DownloadRequest $downloadRequest Here we are sure it is */
        // Check the authorization to access the file
        if (!$downloadRequest->isAccessKeyValid($accessKey)) {
            $downloadRequest->addError(new DownloadRequestError('Invalid access key provided for given document hash.'));
            return $downloadRequest;
        }

        return $downloadRequest;
    }
}