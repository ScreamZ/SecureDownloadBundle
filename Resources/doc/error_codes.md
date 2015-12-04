#Error codes

When a DownloadRequestException is thrown you can access the reason of the exception through `$exception->getReasons()`, which will return an array of DownloadRequestError.

Therefore each instance of DownloadRequestError has an error code, here is the error code list.

## Codes list

### Document Error

* **0** - This is the default error when none has been given.
* **1** - The document has expired or is not found using given hash.
* **2** - The returned document is not an instance of DownloadRequest (you've fetched something that this bundle didn't stored, try to change stash_prefix_key parameter).
* **3** - The returned document is not an instance of DownloadRequest (you've fetched something that this bundle didn't stored, try to change stash_prefix_key parameter).
* **4** - The given filepath to store doesn't exists on the server.
