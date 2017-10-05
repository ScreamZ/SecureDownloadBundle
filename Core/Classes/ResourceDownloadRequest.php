<?php

namespace Screamz\SecureDownloadBundle\Core\Classes;

/**
 * Class ResourceDownloadRequest
 *
 * This class allow to create request object that will be persisted in Stash cache.
 *
 * @author Andréas HANSS <ahanss@kaliop.com>
 */
class ResourceDownloadRequest extends DownloadRequest
{
    /**
     * @var string
     */
    private $resourceData;

    /**
     * DownloadRequest constructor.
     *
     * @param string $resourceIdentifier
     * @param string $accessKey
     */
    public function __construct($resourceIdentifier, $accessKey)
    {
        $this->accessKey = $accessKey;
        $this->resourceData = $resourceIdentifier;
    }

    /**
     * Generate an unique hash for the document using a salt given as parameter and the filepath.
     *
     * Path is unique on the system so there is no name conflict possible.
     *
     * @param string $documentHashSalt
     *
     * @return string
     */
    public function generateTransactionIdentifier($documentHashSalt)
    {
        $resourceHash = md5($documentHashSalt.$this->resourceData);
        $this->transactionID = $resourceHash;

        return $resourceHash;
    }

    /**
     * Get the resource saved data
     *
     * @return string
     */
    public function getTransactionSavedData()
    {
        return $this->resourceData;
    }
}
