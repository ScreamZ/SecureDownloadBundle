#SecureDownloadBundle
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/cfe0a24c-3efb-4bfe-a7f5-a3f00ab41bb3/big.png)](https://insight.sensiolabs.com/projects/cfe0a24c-3efb-4bfe-a7f5-a3f00ab41bb3)

**This bundle is under development, the documentation will be updated through the development process.**

This bundle make it easier and quicker to deploy a secure download document solution, using a Cache engine like Memcached or Redis.

Many cache system are supported thanks to [tedious/TedivmStashBundle!](https://github.com/tedious/TedivmStashBundle).
At the moment the default one using service [@stash]( TedivmStashBundle/Service/CacheService.php), but
feel free to override my service declaration to use your own.

##Basic usage

This is a two-step process, first you need to generate a unique hash, then this one will be used to call the download method.

An access key is known by the encoder and by the decoder. This will allows you to secure the access to your document, it can be a simple hash or string if the context
doesn't depend on it or it can be a salt mixed with a unique identifier of the current logged used, you can also use a cookie to save it or a session variable.
Feel free to try different approaches.

Here is an quick and easy example :

###Rendering the hash in a view or returned it to a web service
```php
    public function generateHashAction()
    {
        $secureDownloader = $this->get('screamz.service.secure_downloader');
        $currentUser = $this->getAuthenticationManager()->getCurrentUser();
    
        // Provided by the server (client don't know it), use something that identify the current logged user.
        $accessKey = md5('somecustomhash'.$currentUser->getUserHash());
    
        try{
            $hash = $secureDownloader->generateHash('/home/site/www/document.txt', $accessKey);
        } catch {DownloadRequestException $e){
            // Do something with errors
            var_dump($e->getReasons());
             
            // Throw a 400 / 500 HTTP exception
            throw new HttpException(500);
        }
        
        // Do something...
        
        // Return response with hash (webservice) or render a template with link to download controller...
    }
```

###Downloading the file using the given hash
```php
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
```

##Documentation

* [Error codes](/Resources/doc/error_codes.md)
* [Default configuration](/Resources/doc/config.md)

