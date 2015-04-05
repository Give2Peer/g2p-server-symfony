To run the features while coding, I suggest you use :

```
$ bin/behat --tags=~geocode
```

Because scenarios tagged with `geocode` use third-party geocoding services with
quotas you might exceed, and subsequently get banned.

You *can* of course run the whole suite, but don't do it too often.