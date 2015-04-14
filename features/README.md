Welcome to the Features
=======================

These describe how the server should respond to HTTP requests from a client,
as a mobile phone for example.

They cover :
- registering
- finding items around various locations :
  - latitude / longitude (preferred)
  - postal addresses
  - IPs
- giving items
- picturing items, as the picture upload is done in a separate request
- many more things in the future, hopefully

The `test_bug` feature is to expose [an inconsistency](https://github.com/Behat/Behat/issues/726)
in our feature runner. This is not related directly to _give2peer_, yet the test
will be kept until a fix is pulled.


Tips
----

To run the features while coding, I suggest you use :

```
$ bin/behat --tags=~geocode
```

Because scenarios tagged with `geocode` use third-party geocoding services with
quotas you might exceed, and subsequently get banned.

You *can* of course run the whole suite, but don't do it too often.