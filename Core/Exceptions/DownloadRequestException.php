<?php

namespace Screamz\SecureDownloadBundle\Core\Exceptions;

use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequest;
use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequestError;

/**
 * Class DownloadRequestException
 *
 * This kind of exception is throw when download request error count is greater than 0, just before the exit point of the bundle (when the hash or the object is returned).
 *
 * The user will have to catch it in his own code.
 *
 * @see Screamz\SecureDownloadBundle\Core\Classes\ErrorCode
 *
 * @author Andréas HANSS <ahanss@kaliop.com>
 */
class DownloadRequestException extends \Exception
{
    /**
     * @var DownloadRequest
     */
    private $downloadRequest;
    /**
     * DownloadRequestException constructor.
     *
     * @param DownloadRequest $downloadRequest
     */
    public function __construct(DownloadRequest $downloadRequest)
    {
        $this->downloadRequest = $downloadRequest;
        parent::__construct('WARNING : A '.__CLASS__.' has been thrown at '.$this->getFile().':'.$this->getLine().', please catch it upstream to avoid this message.');
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $string = '';
        foreach ($this->downloadRequest->getErrors() as $error) {
            $string .= (string)$error . '|';
        }
        return $string;
    }

    /**
     * Get the errors array, you can refer to error codes to handle different cases.
     *
     * @return DownloadRequestError[]
     *
     * @see Screamz\SecureDownloadBundle\Core\Classes\ErrorCode
     */
    public function getReasons()
    {
        return $this->downloadRequest->getErrors();
    }

    /**
     * Return the given document filePath
     *
     * @return string
     */
    public function getDocumentPath()
    {
        return $this->downloadRequest->getRequestSavedData();
    }
}
