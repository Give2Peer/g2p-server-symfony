g2p.give2peer.org
=================

A Symfony project created on April 1, 2015, which makes use of only one small
custom bundle, the `Give2PeerBundle`, and a lot of vendor bundles, blessed be
the `composer` community ; we love you.

This is the REST service running at [g2p.give2peer.org](http://g2p.give2peer.org).

A Work in Progress
==================

These are the features we're working on :

- [X] item location, title, description, tags
- [X] item finding around coordinates
- [X] item images
- [X] list tags
- [X] geolocation through third-party services
- [X] registration
- [ ] https support
- [ ] proper documentation

Right now, the database online is filled with fake/test data, so that we may
easily test the client while developing it.


The Bundle
==========

See [the bundle's README](src/Give2Peer/Give2PeerBundle/README.md).


REST
====

Expect JSON responses.

Authentication
--------------

Use HTTP basic auth.

Yes, we'll need https.

Authentication will still probably be subject to upgrades, like using perishable
authentication tokens instead of the user password each time.


Registration
------------

Clients can register users using the following API :

`POST /register`
  - *username*
  - *password*
  - Returns the user.

See the error codes below to see what the API sends back when the username is
already taken.

This API is throttled to a fixed number of queries per day and per IP.


Give
----

`POST /give`
  - *location* (mandatory)
    Multiple formats are accepted :
      - "43.578658, 1.468091"
      - "10 Rond-Point Jean Lagasse, 31400 Toulouse"
      - "91.121.148.102"
  - *title*
  - *description*

`POST /spot`
  - alias of give, when we're not the legal owner


Find
----

`GET /tags`
  - returns an array of tags, with no guaranteed sorting.
  - each tag is string of maximum 16 characters.


`GET /find/{latitude}/{longitude}`
  - fetches at most 32 items present around the provided coordinates,
    sorted by increasing distance.
  - returns an array of `[ 0 => <item>, 'distance' => <distance> ]`.
    Yes, each result is a weird mixed array. If you can fix that, be my guest.
  - each item is a full JSONed instance with as much data as we need.
  - (todo) provides the pictures URI (get them with a separate request)

Example JSON sent back with two items found :

```
[
  {
    "0": {
      "id": 100,
      "title": "Test item",
      "location": "Toulouse",
      "latitude": 43.578658,
      "longitude": 1.468091,
      "description": null,
      "created_at": {
        "date": "2015-04-06 01:16:22",
        "timezone_type": 3,
        "timezone": "Europe\/Paris"
      },
      "updated_at": {
        "date": "2015-04-06 01:16:22",
        "timezone_type": 3,
        "timezone": "Europe\/Paris"
      },
      "giver": null,
      "spotter": null
    },
    "distance": "148.019325545116"
  },
  {
    "0": {
      "id": 101,
      "title": "Test item",
      "location": "Toulouse",
      "latitude": 43.566591,
      "longitude": 1.474969,
      "description": null,
      "created_at": {
        "date": "2015-04-06 01:16:22",
        "timezone_type": 3,
        "timezone": "Europe\/Paris"
      },
      "updated_at": {
        "date": "2015-04-06 01:16:22",
        "timezone_type": 3,
        "timezone": "Europe\/Paris"
      },
      "giver": null,
      "spotter": null
    },
    "distance": "1601.20720473937"
  }
]
```


Error Codes
-----------

Still not sure about this. Maybe start at 700 ? Maybe use strings ?

001 UNAVAILABLE_USERNAME Username already taken
002 BANNED_FOR_ABUSE     Too many registrations
003 UNSUPPORTED_FILE     Wrong or missing picture file uploaded
004 NOT_AUTHORIZED       Not authorized

Todo
----

`POST /paint/{item}`
  - adds a file {image} to {item}


`GET /item/{item}/picture_{n}.jpg`
  - WebP might be nice
  - {n} is provided by the item properties, and maybe we'll provide the URI


