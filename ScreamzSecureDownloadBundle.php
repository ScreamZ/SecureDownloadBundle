<?php

namespace Screamz\SecureDownloadBundle;

use Screamz\SecureDownloadBundle\DependencyInjection\ScreamzSecureDownloadExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ScreamzSecureDownloadBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new ScreamzSecureDownloadExtension();
    }
}
