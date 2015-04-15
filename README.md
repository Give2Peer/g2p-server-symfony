g2p.give2peer.org
=================

A Symfony project created on April 1, 2015, which makes use of only one small
custom bundle, the `Give2PeerBundle`, and a lot of vendor bundles, blessed be
the `composer` community ; we love you.

This is the REST service running at [g2p.give2peer.org](http://g2p.give2peer.org).

It will provide a server for [Give2Peer](http://www.give2peer.org)'s bêta Android application.

It is not unit-tested, as are most APIs, but [behavior-tested](/features).
Features written in Gherkin are a breeze to write and review, they provide an
easy entry point to new contributors and they also provide documentation.


Vision
======

Bring the power of the internet of things to the sharing of things.



A Work in Progress
==================

These are the features we're working on :

- [X] item location, title, description, tags
- [X] item finding around coordinates
- [X] item images
- [X] list tags
- [X] geolocation through third-party services
- [X] registration
- [ ] create and serve 200x200 thumbnails
- [ ] https support
- [ ] proper documentation

Right now, the database online is filled with fake/test data, so that we may
easily test the client while developing it.


The Bundle
==========

See [the bundle's README](src/Give2Peer/Give2PeerBundle/README.md).


REST
====

Expect `JSON` responses.


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

Here is an example of `JSON` sent back with two items found :

```
[
  {
    "id": 529,
    "title": "Plum maiores",
    "location": "43.59528538094, 1.4899757103897",
    "latitude": 43.59528538094,
    "longitude": 1.4899757103897,
    "distance": "2494.63965368956",
    "description": "Consequuntur rem quod ab omnis aut aut nesciunt quaerat.",
    "tags": [],
    "created_at": {
      "date": "2015-04-08 16:50:16",
      "timezone_type": 3,
      "timezone": "Europe\/Paris"
    },
    "updated_at": {
      "date": "2015-04-08 16:50:16",
      "timezone_type": 3,
      "timezone": "Europe\/Paris"
    },
    "giver": null,
    "spotter": null
  },
  {
    "id": 76,
    "title": "LimeGreen libero",
    "location": "43.548083594727, 1.4953072156219",
    "latitude": 43.548083594727,
    "longitude": 1.4953072156219,
    "distance": "4194.49735510112",
    "description": "Eum dolore saepe repellendus autem accusantium inventore.",
    "tags": [],
    "created_at": {
      "date": "2015-04-08 16:50:12",
      "timezone_type": 3,
      "timezone": "Europe\/Paris"
    },
    "updated_at": {
      "date": "2015-04-08 16:50:12",
      "timezone_type": 3,
      "timezone": "Europe\/Paris"
    },
    "giver": null,
    "spotter": null
  }
]
```


Pictures
--------

`POST /pictures/{itemId}`
  - Send a JPG file in a input named 'picture'.

`GET /pictures/{itemId}/1.jpg`


Error Codes
-----------

Still not sure about this.

```
001 UNAVAILABLE_USERNAME Username already taken
002 BANNED_FOR_ABUSE     Too many registrations
003 UNSUPPORTED_FILE     Wrong or missing picture file uploaded
004 NOT_AUTHORIZED       Not authorized
005 SYSTEM_ERROR         System error, usually a bad setup
```