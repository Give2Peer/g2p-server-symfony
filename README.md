Give2Peer PHP server
====================


Why
===

Bring the power of the internet of things to the sharing of things.

That is so cheesy, yes. But it's also true !
We can leverage this awesome new communication tool that internet is
to find people that need the stuff we have but don't need.
Likewise, artists, tinkerers, and creators will have a field day !

_Someone's garbage is someone else's treasure._

_Want not, waste not._

Finally, and this is quite important, _money should not be involved_.

How about sharing pictures and credits instead with the people that gave you
the materials you needed for that awesome creation you just made ?


What
====

A Symfony project created on April 1, 2015, which makes use of only one small
custom bundle, the `Give2PeerBundle`, and a lot of vendor bundles, blessed be
the community ; we love you.

This is the REST service running at [g2p.give2peer.org](http://g2p.give2peer.org).

It will provide a server for [Karma](http://www.give2peer.org), the Android application.

It is extensively [behavior-tested](/features).


A Work in Progress
==================

These are the features we're working on :

- [X] item location, title, description, tags
- [X] item finding around coordinates
- [X] item images
- [X] list tags
- [X] geolocation through third-party services
- [X] registration
- [X] create and serve 200x200 thumbnails
- [X] user karma points and levels
- [X] REST API documentation
- [X] version the API
- [ ] https support

Right now, the database online is filled with fake/test data, so that we may
easily test the client while developing it.


The Bundle
==========

See [the bundle's README](src/Give2Peer/Give2PeerBundle/README.md).


REST API
========

See the full and interactive [documentation](http://g2p.give2peer.org) online.



Error Codes
-----------

The error codes are available as constants in the class `Controller\ErrorCode`.

```
001 UNAVAILABLE_USERNAME Username already taken
002 BANNED_FOR_ABUSE     Too many registrations
003 UNSUPPORTED_FILE     Wrong or missing picture file uploaded
004 NOT_AUTHORIZED       Not authorized
005 SYSTEM_ERROR         System error, usually a bad setup
006 BAD_LOCATION         Provided location could not be resolved to coordinates
007 UNAVAILABLE_EMAIL    Email already taken
008 EXCEEDED_QUOTA       User daily quota for that action was exceeded
009 BAD_USERNAME         Provided username could not be found
```

Install
=======

Get composer, install the dependencies.

Then look at and run the setup scripts as-needed in `script/`.

If behat complains about overriding `createKernel`,
just hit it on the head once or twice !
Alternatively, update the LiipFunctionalBundle vendor
when our pull request makes it to release. :)


Blackboard
==========

All the REST methods are done "by hand", and support only JSON.
Maybe move to a better way of doing APIs, like using :
https://github.com/dunglas/DunglasApiBundle

Also, the API should be versioned.