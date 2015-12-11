<?php

namespace Screamz\SecureDownloadBundle\Core\Classes\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class BlobResponse
 *
 * This response should be used when you need to return a blob (Like base64 encoded image).
 *
 * @author Andréas HANSS <ahanss@kaliop.com>
 */
class BlobResponse extends Response
{
    /**
     * BlobResponse constructor.
     *
     * @param string $documentPath The path of the document to encode as B64
     * @param int    $status
     * @param array  $headers
     *
     */
    public function __construct($documentPath, $status = 200, array $headers = array())
    {
        $content = base64_encode(file_get_contents($documentPath));
        $finfoMineType = finfo_open(FILEINFO_MIME_TYPE);
        $headers['Content-Type'] = finfo_file($finfoMineType, $documentPath);
        parent::__construct($content, $status, $headers);
    }
}
