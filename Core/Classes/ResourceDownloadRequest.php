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
    private $resourceIdentifier;

    /**
     * DownloadRequest constructor.
     *
     * @param string $resourceIdentifier
     * @param string $accessKey
     */
    public function __construct($resourceIdentifier, $accessKey)
    {
        parent::__construct(null, $accessKey);
        $this->resourceIdentifier = $resourceIdentifier;
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
    public function generateRequestHash($documentHashSalt)
    {
        $resourceHash = md5($documentHashSalt.$this->resourceIdentifier);
        $this->hash = $resourceHash;

        return $resourceHash;
    }

    /**
     * @inheritdoc
     */
    public function isProcessable()
    {
         return count($this->getErrors()) === 0;
    }
}
