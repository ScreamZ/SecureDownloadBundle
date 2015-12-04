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
     * DownloadRequestException constructor.
     *
     * @param DownloadRequest $downloadRequest
     */
    public function __construct(DownloadRequest $downloadRequest)
    {
        $this->downloadRequest = $downloadRequest;
        parent::__construct('An '.__CLASS__.' has been thrown, please catch it to handle this error case.');
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
}