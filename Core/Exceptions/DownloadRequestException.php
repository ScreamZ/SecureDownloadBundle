<?php

namespace Screamz\SecureDownloadBundle\Core\Exceptions;

use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequest;
use Screamz\SecureDownloadBundle\Core\Classes\DownloadRequestError;

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
        parent::__construct();
    }

    /**
     * Get the errors
     *
     * @return DownloadRequestError[]
     */
    public function getReasons()
    {
        return $this->downloadRequest->getErrors();
    }
}