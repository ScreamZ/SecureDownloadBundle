<?php

namespace Screamz\SecureDownloadBundle\Core\Classes;


class DownloadRequestError
{
    /** @var int */
    private $errorCode;
    /** @var string string */
    private $message;

    /**
     * DownloadRequestError constructor.
     *
     * @param int    $errorCode Use one const of {@link ErrorCode}
     * @param string $message
     */
    public function __construct($errorCode, $message = 'DownloadRequest Error')
    {
        $this->errorCode = $errorCode;
        $this->message = $message;
    }

    /**
     * Return the error code, please referer to documentation.
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Get the corresponding message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}