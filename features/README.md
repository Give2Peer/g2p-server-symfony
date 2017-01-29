Welcome to the Features
=======================

The `.feature` files contain _scenarios_ such as :

``` gherkin
Feature: Hindering spammers
  In order to own your brain
  As a spammer
  I want to try to abuse the system

Background:
  Given I am the registered user named "V14Gr4"

Scenario: Fail to exceed level 0 daily quota of 2
  Given I am level 0
    And I gave 2 items 1 minute ago
   Then there should be 2 items in the database
   When I try to give an item
   Then I should fail
    And there should still be 2 items in the database
```

You don't need to know how to code to read them, or even edit them !

The best part is that each line is automagically ran as code that either
succeeds or fails, so that the developers know if they broke something
or if their newly added feature is done yet.

We first write the specifications in those files, and then code until all's green !

You can read [the latest run of all the features](https://travis-ci.org/Give2Peer/g2p-server-symfony/)
on our continuous integration platform, Travis.


What
----

These describe how the server should respond to HTTP REST requests from a
client, say a mobile app.

They cover :
- registering
- editing profile
- finding items
  - around various locations :
      - latitude / longitude _(preferred)_
      - postal addresses
      - IPs _(that one randomly fails)_
  - with pagination (to review)
  - filtered by tags (to do)
- giving items
  - gaining karma
  - daily quotas
- levelling up
- picturing items, as the picture upload is done in a separate request
- statistics (early stages)

That's pretty much everything this API offers right now.


How
---

We needed to customize the feature runner script, so this setup script only
symlinks `bin/behat` to point to `script/behat`.

Then, to perfunctorily run the features while coding, I suggest you use :

```
$ script/behat --tags=~geocode -vv
```

Because scenarios tagged with `geocode` use third-party geocoding services with
quotas you might exceed, and subsequently get banned.

You *can* of course run the whole suite, but don't do it too often.


Notes
-----

You should install the `fortunes` package. It's optional, as fun is.

As of January 2017, we have :

    72 scenarios (72 passed)
    540 steps (540 passed)

Running the whole test suite takes about one full minute.
[Travis](https://travis-ci.org/Give2Peer/g2p-server-symfony/) is going to do it
automatically for each commit and pull request.


FAQ
---

Q: What's the tech behind that ?
A: _[Behat](http://docs.behat.org) runs [Gherkin](http://docs.behat.org/en/v3.0/guides/1.gherkin.html)._

Q: It's unusual to test an API using Gherkin ! Why ?
A: _For some, I would not recommend it. But for that one, 5/5 would do again._


Try
---

```
$ sudo apt-get install composer fortunes
$ cd <project root where composer.json resides>
$ composer install
$ script/reset_db_schema
$ bin/behat -vv --tags=~geocode