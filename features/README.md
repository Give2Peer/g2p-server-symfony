Welcome to the Features
=======================

These describe how the server should respond to HTTP REST requests from a client,
say a mobile app.

They cover :
- registering
- finding items around various locations :
  - latitude / longitude (preferred)
  - postal addresses
  - IPs
  - pagination
- giving items
  - gaining experience
  - daily quotas
- picturing items, as the picture upload is done in a separate request
- many more things in the future, hopefully


How
---

To run the features while coding, I suggest you use :

```
$ bin/behat --tags=~geocode
```

Because scenarios tagged with `geocode` use third-party geocoding services with
quotas you might exceed, and subsequently get banned.

You *can* of course run the whole suite, but don't do it too often.