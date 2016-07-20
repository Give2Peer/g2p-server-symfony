Give2Peer API for Karma ☯
=========================

What
====

The server-side of mobile apps such as _Karma_, for features such as :

``` gherkin
Feature: Find an item I lost
  In order to find something I lost
  As a sentient and connected being
  I want to look for lost items

Feature: Find repairable furniture
  In order to add fixtures to my home
  As a sentient and connected being
  I want to repair discarded furniture

Feature: Find materials
  In order to craft something awesome
  As a sentient and connected being
  I want to find discarded materials


Feature: Geotagging items
  In order to bring joy to all of the above
  As a nice, sentient, and connected being
  I want to geotag items in public places

Scenario: Lost & Found
  Given I am strolling in Paris
   When I geotag a lost glove below the Eiffel Tower
   Then I should win some karma
    And the glove should be added to the map of lost items
    And glove lookers in the vicinity should be alerted

Scenario: Donation
  Given I am moving out
    And I have extra furniture
   When I donate that extra furniture
   Then I should win some karma
    And the furniture should be added to the map of donations
    And gatherers in the vicinity should be alerted

Scenario: Matter Out Of Place (MOOP)
  Given I am a nobody
   When I photograph and geotag some MOOP at 41.694151, -0.156661
   Then I should win some karma
    And the MOOP should be added to the MOOP bag
```

The full list of behaviors is available in [`features/`](/features).

This is the REST service running at [g2p.give2peer.org](http://g2p.give2peer.org).

It will provide a server for [Karma](http://www.give2peer.org), the Android app.


Why
===

To bring the power of the internet of things to the sharing of things !

That is cheesy, yes. But it's also true !
We can leverage this awesome new communication tool that internet is
to find people that need the stuff we have but don't need.
Artists, tinkerers, and makers will have a field day !

_Someone's garbage is someone else's treasure._

_Want not, waste not._

Finally, and this is quite important, _money should not be involved_.

How about sharing pictures and credits instead with the people that gave you
the materials you needed for that awesome creation you just made ?


How
===

A [Symfony2] project created on April 1, 2015, which makes use of only one small
custom bundle, the `Give2PeerBundle`, and a lot of vendor bundles, blessed be
the community ; we love you.

The chosen database is [pgSQL], because we need some of its extensions.


A Work in Progress
==================

RoadMap
-------

### Perhaps

- [ ] Feature: API Text Responses
- [ ] Feature: API XML Responses


### 1.1

- [ ] More RESTful routes for Users
- [ ] More RESTful routes for Items
- [ ] More RESTful routes for Tags
- [ ] Testing: a sandbox version of this server to test-drive clients
- [ ] Setting: move route configuration to annotations


### 1.0

- [x] Feature: Stats about the current number of items and users
- [x] Setting: Version the API (ie: prefix with `v1/` in the URLs)
- [x] Setting: Optional HTTPS (somewhat, we're still self-signed)
- [x] Setting: Semantic bundle configuration
- [x] Provide stats about the total number of items
- [x] Feature: Attach a `png` picture to an item
- [x] Feature: Attach a `gif` picture to an item
- [x] Feature: Attach a `webp` picture to an item
- [x] Feature: Delete my own items
- [x] Feature: Report an abusive item (the irony of writing censorship software is not lost on me)
- [ ] Freeze the API: Pictures upload need work.


ChangeLog
---------

### 0.4

- [x] Feature: Item location, title, type, description, tags, images
- [x] Feature: Find items by closeness to lat/lng coordinates
- [x] Feature: List tags
- [x] Feature: Geolocation through third-party services
- [x] Setting: HTTP Auth authentication and easy registration
- [x] Feature: Create, store and serve 240x240 thumbnails
- [x] Feature: Attach a `jpg` picture to an item
- [x] Feature: Karma points and levels
- [x] Feature: API auto-generated documentation (NelmioApiBundle)




The Bundle
==========

There's only one Give2Peer Symfony2 Bundle for now.
There's no need for many right now, and it will make refactoring to an API
framework such as https://github.com/api-platform/api-platform easier if want to.

See [the bundle's README](src/Give2Peer/Give2PeerBundle/README.md).



REST API
========

See the full and interactive [documentation](http://g2p.give2peer.org) online.


Error Codes
-----------

The error codes are available as constants in the class `Controller\ErrorCode`.

```
001 UNAVAILABLE_USERNAME Username already taken
002 BANNED_FOR_ABUSE     Too many registrations, usually
003 UNSUPPORTED_FILE     Wrong or missing picture file uploaded
004 NOT_AUTHORIZED       Wrong credentials or permissions
005 SYSTEM_ERROR         System error, usually a bad setup
006 BAD_LOCATION         Provided location could not be resolved to coordinates
007 UNAVAILABLE_EMAIL    Email already taken
008 EXCEEDED_QUOTA       User daily quota for that action was exceeded
009 BAD_USERNAME         Provided username could not be found
010 BAD_ITEM_TYPE        Provided item type is unknown
```



Install
=======

You'll need `php >= 5.6`.

The vendor setup is pretty straightforward if you already have [Composer] :

    composer install

Otherwise, here's how to get [Composer] and install :

    curl -s https://getcomposer.org/installer | php
    php composer.phar install

Alternatively, [Composer] is also available as a Debian package :

    apt-get install composer


Then look at and run the setup scripts as-needed in `script/`.

You should probably install the `fortune` package too, but it's not mandatory.
Neither is fun, think about it.



Licence
=======

Everything is public domain, unless specified otherwise in the file.

We should collect the various licences of the third-party libs we use,
and make a proper `LICENCE.md` file that we can display in the clients.

They are all public-domain-like, but they have their little quirks, and
besides it's like saying thanks !



[Symfony2]: https://symfony.com/
[pgSQL]: https://www.postgresql.org/
[Composer]: https://getcomposer.org/