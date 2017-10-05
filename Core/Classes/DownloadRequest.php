<?php

namespace Screamz\SecureDownloadBundle\Core\Classes;

/**
 * Class DownloadRequest
 *
 * This class allow to create request object that will be persisted in Stash cache.
 *
 * @author Andréas HANSS <ahanss@kaliop.com>
 */
class DownloadRequest
{
    private $filePath;
    /** @var DownloadRequestError[] */
    protected $requestErrors = [];
    protected $accessKey;
    protected $transactionID;
    protected $resourceName;
    protected $mimeType;

    /**
     * DownloadRequest constructor.
     *
     * @param string $filePath
     * @param string $accessKey
     */
    public function __construct($filePath = null, $accessKey = null)
    {
        $this->accessKey = $accessKey;
        $this->filePath = $filePath;

        if (file_exists($filePath)) {
            $this->resourceName = basename($filePath);
            $this->mimeType = mime_content_type($filePath);
        } else {
            $this->addError(new DownloadRequestError(ErrorCode::INVALID_FILEPATH, 'File path does not exist on the server.'));
        }

    }

    /**
     * Generate an unique hash for the transaction using a salt given as parameter and the filepath.
     *
     * Path is unique on the system so there is no name conflict possible.
     *
     * @param string $transactionHashSalt
     *
     * @return string
     */
    public function generateTransactionIdentifier($transactionHashSalt)
    {
        $transactionID = md5($transactionHashSalt.$this->filePath);
        $this->transactionID = $transactionID;

        return $transactionID;
    }

    /**
     * Check whether the download request can be handled, meaning it has no errors.
     *
     * @return boolean
     */
    public function isProcessable()
    {
        return count($this->getErrors()) === 0;
    }

    /**
     * Compare the given accessKey with the one provided on transaction generation.
     *
     * @param string $accessKey
     *
     * @return bool
     */
    public function isAccessKeyValid($accessKey)
    {
        return $this->accessKey === $accessKey;
    }

    /**
     * Add a new error to the request.
     *
     * @param DownloadRequestError $error
     */
    public function addError(DownloadRequestError $error)
    {
        $this->requestErrors[] = $error;
    }

    /**
     * Get the error list
     *
     * @return DownloadRequestError[]
     */
    public function getErrors()
    {
        return $this->requestErrors;
    }

    /**
     * Get the document real path, allowing to download it.
     *
     * @return string
     */
    public function getTransactionSavedData()
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function getResourceName()
    {
        return $this->resourceName;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Get the document hash
     *
     * @return string
     */
    public function getTransactionID()
    {
        return $this->transactionID;
    }
}
