# SecureDownloadBundle

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/cfe0a24c-3efb-4bfe-a7f5-a3f00ab41bb3/big.png)](https://insight.sensiolabs.com/projects/cfe0a24c-3efb-4bfe-a7f5-a3f00ab41bb3)

This bundle make it easier and quicker to deploy a secure download document solution, using a Cache engine like Memcached or Redis.

Many cache system are supported thanks to [tedious/TedivmStashBundle!](https://github.com/tedious/TedivmStashBundle).
At the moment the default one using service [@stash]( TedivmStashBundle/Service/CacheService.php), but
feel free to override my service declaration to use your own.

## Basic usage

In order to access a secured resource or file you need to pre-authorize this one.

You either need a file path on the system (full path) or some data you want to save. This can be whole base64 data,
just some data that will be required to fetch some others data (like through a web-service).

In order to achieve that, you need to generate an access key that is known by the encoder and the decoder.
This will allows you to secure the access to your resource or file, it can be a simple hash or string if the context
doesn't depend on it or it can be a salt mixed with a unique identifier of the current logged used, you can also use a cookie to save it or a session variable.
Feel free to try different approaches.

Here are quick example of common use case:

### I have a path on my API, that I don't want to expose on the frontend

#### TransactionID generation
This path must be only accesible to the guy that triggered his generation too. If you share the download link to someone else
this guy will not be able to download it.

For that we need something that identify in a unique manner the user who triggered the transactionID hash. The userID is perfect.
If we wanted to allow sharing or the download link we could have used something that is not user-dependant.

```php
public function generateHashAction()
{
    $secureDownloader = $this->get('screamz.service.secure_downloader');
    $currentUser = $this->getAuthenticationManager()->getCurrentUser();

    // Provided by the server (client don't know it), use something that identify the current logged user.
    $accessKey = md5('somecustomhash'.$currentUser->getId());

    try{
        // This return a string
        $transactionID = $secureDownloader->preAuthorizeDocumentPath('/home/site/www/document.txt', $accessKey);
    } catch {DownloadRequestException $e){
        // Do something with errors
        var_dump($e->getReasons());
         
        // Throw a 400 / 500 HTTP exception
        throw new HttpException(500);
    }
    
    // Do something...
    
    // Return response with the transactionID or render a template with link to download controller...
}
```

#### Downloading the file using the given transactionID in a secured way
```php
public function downloadAction($transactionID)
{
    $secureDownloader = $this->get('screamz.service.secure_downloader');
    $currentUser = $this->getAuthenticationManager()->getCurrentUser();

    // Provided by the server (client don't know it), use something that identify the current logged user.
    $accessKey = md5('somecustomhash'.$currentUser->getId());
    
    try {
        $binaryResponse = $secureDownloader->getResourceBinaryFileResponse($transactionID, $accessKey);
        return $binaryResponse;
    } catch (DownloadRequestException $e) {
        // Do something with errors
        var_dump($e->getReasons());
        
        // Throw a 400 / 500 HTTP exception
        throw new HttpException(500);
    }
}
```

### I want to save data that will allow me to query a remote API later in order to get something

#### Generate a transactionID

```php
public function generateHashAction()
{
    $secureDownloader = $this->get('screamz.service.secure_downloader');
    $currentUser = $this->getAuthenticationManager()->getCurrentUser();

    // Provided by the server (client don't know it), use something that identify the current logged user.
    $accessKey = md5('somecustomhash'.$currentUser->getId());

    try{
        // This return a string
        $transactionID = $secureDownloader->preAuthorizeResource(json_encode(['token' => 'sometoken'], $accessKey);
    } catch {DownloadRequestException $e){
        // Do something with errors
        var_dump($e->getReasons());

        // Throw a 400 / 500 HTTP exception
        throw new HttpException(500);
    }

    // Do something...

    // Return response with the transactionID or render a template with link to download controller...
}
```

#### Retrieve the resource after checking authorization
```php
public function downloadAction($transactionID)
{
    $secureDownloader = $this->get('screamz.service.secure_downloader');
    $currentUser = $this->getAuthenticationManager()->getCurrentUser();

    // Provided by the server (client don't know it), use something that identify the current logged user.
    $accessKey = md5('somecustomhash'.$currentUser->getId());

    try {
        $resource = $secureDownloader->getResource($transactionID, $accessKey);
    } catch (DownloadRequestException $e){
        throw $this->createAccessDeniedException('Accès à la ressource non autorisé.');
    }

    $params = json_decode($resource->getTransactionSavedData(), true);

    // Call Webservice from here using $params
}
```
## Documentation

* [Error codes](/Resources/doc/error_codes.md)
* [Default configuration](/Resources/doc/config.md)

