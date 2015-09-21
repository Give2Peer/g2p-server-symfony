give2peer php server
====================


why
===

bring the power of the internet of things to the sharing of things.

that is so cheesy, yes. but it's also true !
we can leverage this awesome new communication tool that internet is
to find people that need the stuff we have but don't need.
likewise, artists, tinkerers, and creators will have a field day !

_someone's garbage is someone else's treasure._

_want not, waste not._

finally, and this is quite important, _money should not be involved_.

how about sharing pictures and credits instead with the people that gave you
the materials you needed for that awesome creation you just made ?


what
====

a symfony project created on april 1, 2015, which makes use of only one small
custom bundle, the `give2peerbundle`, and a lot of vendor bundles, blessed be
the community ; we love you.

this is the rest service running at [g2p.give2peer.org](http://g2p.give2peer.org).

it will provide a server for [give2peer](http://www.give2peer.org)'s bêta android application.

it is extensively [behavior-tested](/features).


a work in progress
==================

these are the features we're working on :

- [x] item location, title, description, tags
- [x] item finding around coordinates
- [x] item images
- [x] list tags
- [x] geolocation through third-party services
- [x] registration
- [x] create and serve 200x200 thumbnails
- [x] user experience points and levels
- [x] rest api documentation
- [ ] https support

right now, the database online is filled with fake/test data, so that we may
easily test the client while developing it.


the bundle
==========

see [the bundle's readme](src/give2peer/give2peerbundle/readme.md).


rest
====

expect `json` responses.


authenticate
------------

use http basic auth.

yes, we'll need https.

authentication will still probably be subject to upgrades, like using perishable
authentication tokens instead of the user password each time.


register
--------

clients can register users using the following api :

`post /register`
  - *username*
  - *password*
  - returns the user.

see the error codes below to see what the api sends back when the username is
already taken.

this api is throttled to a fixed number of queries per day and per ip.


give or spot
------------

`post /item/add`
  - *location* (mandatory)
    multiple formats are accepted :
      - "43.578658, 1.468091"
      - "10 rond-point jean lagasse, 31400 toulouse"
      - "91.121.148.102"
  - *title*
  - *description*
  - *gift* 'true' or 'false', whether the user is the legal owner or not



find
----

`get /tags`
  - returns an array of tags, with no guaranteed sorting.
  - each tag is a string of maximum 16 characters.


`get /find/{latitude}/{longitude}/{skip}/{radius}`
  - fetches at most `64` items present around the provided coordinates,
    sorted by increasing distance, at most `radius`, and `skip` the first ones.
  - the `radius` is expected in meters, and by default is infinite.
  - the number of items to `skip` is 0 by default.
  - returns an array of items, each with the additional `distance` property.
  - each item is a full jsoned instance with as much data as we need.
  - each item provides its picture uri (get the file with a separate request)

here is an (old) example of `json` sent back with two items found :

```
[
  {
    "id": 529,
    "title": "plum maiores",
    "location": "43.59528538094, 1.4899757103897",
    "latitude": 43.59528538094,
    "longitude": 1.4899757103897,
    "distance": "2494.63965368956",
    "description": "consequuntur rem quod ab omnis aut aut nesciunt quaerat.",
    "tags": [],
    "created_at": {
      "date": "2015-04-08 16:50:16",
      "timezone_type": 3,
      "timezone": "europe\/paris"
    },
    "updated_at": {
      "date": "2015-04-08 16:50:16",
      "timezone_type": 3,
      "timezone": "europe\/paris"
    },
    "giver": null,
    "spotter": null
  },
  {
    "id": 76,
    "title": "limegreen libero",
    "location": "43.548083594727, 1.4953072156219",
    "latitude": 43.548083594727,
    "longitude": 1.4953072156219,
    "distance": "4194.49735510112",
    "description": "eum dolore saepe repellendus autem accusantium inventore.",
    "tags": [],
    "created_at": {
      "date": "2015-04-08 16:50:12",
      "timezone_type": 3,
      "timezone": "europe\/paris"
    },
    "updated_at": {
      "date": "2015-04-08 16:50:12",
      "timezone_type": 3,
      "timezone": "europe\/paris"
    },
    "giver": null,
    "spotter": null
  }
]
```


pictures
--------

`post /pictures/{itemid}`
  - send a jpg file in a input named 'picture'.
  - that picture will be renamed `1.jpg`.
  - this is terrible and subject to future changes.

`get /pictures/{itemid}/1.jpg`


error codes
-----------

the error codes are available as constants in the class `controller\Errorcode`.

```
001 unavailable_username username already taken
002 banned_for_abuse     too many registrations
003 unsupported_file     wrong or missing picture file uploaded
004 not_authorized       not authorized
005 system_error         system error, usually a bad setup
006 bad_location         provided location could not be resolved to coordinates
007 unavailable_email    email already taken
008 exceeded_quota       user daily quota for that action was exceeded
```


blackboard
==========

all the rest methods are done "by hand", and support only json.
maybe move to a better way of doing apis, like using :
https://github.com/dunglas/dunglasapibundle

also, the api should be versioned.