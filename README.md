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
- [ ] Setting: move route configuration to annotations
- [ ] Ensure RESTfulnessimo


### 1.0

- [x] Feature: I18N
- [x] Feature: Support French
- [ ] Feature: Add multiple pictures to an item
- [ ] Feature: Upload a picture before uploading an item
- [ ] Setting: Freeze the API and release


ChangeLog
---------

### 0.5

- [x] Feature: Stats about the current number of items and users
- [x] Setting: Version the API (ie: prefix with `v1/` in the URLs)
- [x] Setting: Optional HTTPS (somewhat, we're still self-signed)
- [x] Setting: Semantic bundle configuration
- [x] Feature: Stats about the total number of items and users
- [x] Feature: Attach a `png`, `gif` or `webp` picture to an item
- [x] Feature: Delete my own items
- [x] Feature: Report an abusive item (the irony of me writing censorship software is not lost on me)
- [x] Feature: Cancel a report of an abusive item
- [x] Licence: Public domain and vendors' libre licences


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



Install
=======

Vendors
-------

You'll need `php >= 5.6`.

Here are the packages you'll need at first on Debian-based systems :

    apt install php php-xml php-intl php-mbstring php-curl php-gd composer

The vendor setup is pretty straightforward if you have [Composer] :

    composer install


Database and Testing
--------------------

Then look at and run the `setup_` scripts as-needed in `script/` :

- `script/setup_pgsql`
- `script/setup_permissions`

They're made for Debian, you're welcome to add your own.

You should probably install the `fortune` package too, but it's not mandatory.
Neither is fun, think about it.

    apt install fortunes



Licence
=======

Everything here is _public domain_, unless specified otherwise in the file trunk.

See `LICENCE.md`.






[Symfony2]: https://symfony.com/
[pgSQL]: https://www.postgresql.org/
[Composer]: https://getcomposer.org/