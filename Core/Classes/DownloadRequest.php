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
    private $requestErrors;
    private $accessKey;
    private $hash;

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
        $documentHash = md5($documentHashSalt.$this->filePath);
        $this->hash = $documentHash;

        return $documentHash;
    }

    /**
     * Check wether the download request can be handled (save / download) and the file is available from filesystem.
     *
     * @return boolean
     */
    public function isProcessable()
    {
        if (!file_exists($this->filePath)) {
            $this->addError(new DownloadRequestError(ErrorCode::INVALID_FILEPATH, 'File path does not exist on the server.'));
        }

        return count($this->getErrors()) === 0;
    }

    /**
     * Compare the given accessKey with the one provided on document hash generation.
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
     * Add a new error to the request
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
     * @return array
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
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Get the document hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }
}
