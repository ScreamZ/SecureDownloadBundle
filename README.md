#ScreamzSecureDownloadBundle

**This bundle is under development, the documentation will updated**

This bundle help you to set up a secure download document solution quickly using a Cache engine like Memcached or Redis.

Many cache system are supported thanks to [tedious/TedivmStashBundle!](https://github.com/tedious/TedivmStashBundle).
At the moment the default one using service [@stash]( TedivmStashBundle/Service/CacheService.php), but
feel free to override my service declaration to use your own.

##Use case

###Rendering the hash in view or returned to a webservice

    public function generateHashAction()
    {
        $secureDownloader = $this->get('screamz.service.secure_downloader');
        $currentUser = $this->getAuthenticationManager()->getCurrentUser();
    
        // Provided by the server (client don't know it), use something that identify the current logged user.
        $accessKey = md5('somecustomhash'.$currentUser->getUserHash());
    
        $hash = $secureDownloader->generateHash('/home/site/www/document.txt', $accessKey);
        
        // Return response with hash (webservice) or render a template with link to download controller...
        
        ...
    }

###Downloading the file for the given hash

    public function downloadAction($hash)
    {
        $secureDownloader = $this->get('screamz.service.secure_downloader');
        $currentUser = $this->getAuthenticationManager()->getCurrentUser();

        // Provided by the server (client don't know it), use something that identify the current logged user.
        $accessKey = md5('somecustomhash'.$currentUser->getId());
        
        try {
            $binaryResponse = $secureDownloader->downloadHash($hash, $accessKey);
            return $binaryResponse;
        } catch (DownloadRequestException $e) {
            // Do something with errors
            var_dump($e->getReasons());
            
            // Throw a 400 / 500 HTTP exception
            throw new HttpException(500);
        }
    }

##Configuration

You can configure the following parameters by adding them to your config.yml

    secure_download:
        stashPrefixKey: anystring           #Prefix key for each entry in stash (let default unless you're encountering conflicts).
        documentHashSalt: anysaltstring     #Salt used in the hash generation, set your own if you want more security.