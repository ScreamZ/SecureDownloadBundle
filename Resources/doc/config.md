# Default configuration annotated

This is the default configuration when none is defined.

```
    secure_download:
        cache:
            default_ttl: 300 # The default TTL for the stash cache when generating a document.
            stash_prefix_key: secure_download_bundle # A prefix to prevent conflicts in stash engine.
        document:
            hash_salt: screamzSecureDownloader #A salt to improve security of your hash.
```
