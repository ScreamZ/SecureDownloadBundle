<?php

namespace Screamz\SecureDownloadBundle\Core\Classes;

class DownloadRequestError
{

    /**
     * DownloadRequestError constructor.
     *
     * @param $message
     *
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}