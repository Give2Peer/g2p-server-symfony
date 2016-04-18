@rest
Feature: Giving items
  In order to bring joy
  As a nice being
  I want to give items for free


Background:
  Given I am the registered user named "Goutte"


## WRONG LOCATIONS #############################################################


Scenario: Give an item without a location
  When I give the following item :
"""
nope: I'm not going to tell you where it is !
"""
  Then the request should not be accepted
   And there should be 0 items in the database


@geocode
Scenario: Give an item with an ungeolocalizable location
  When I give the following item :
"""
location: The ass-end of nowhere !
"""
  Then the request should not be accepted
   And there should be 0 items in the database


## GOOD LOCATIONS ##############################################################


Scenario: Give an item with only a latitude/longitude location
  When I give the following item :
"""
location: -1.441/43.601
"""
  Then the request should be accepted
   And the response should include :
"""
item:
  location: -1.441/43.601
  latitude: -1.441
  longitude: 43.601
"""
   And there should be 1 item in the database


Scenario: Give an item with only a latitude, longitude location
  When I give the following item :
"""
location: -1.441, 43.601
"""
  Then the request should be accepted
   And the response should include :
"""
item:
  location: -1.441, 43.601
  latitude: -1.441
  longitude: 43.601
"""
   And there should be 1 item in the database


Scenario: Give an item with a weird latitude/longitude location
  When I give the following item :
"""
location: 2/.12
"""
  Then the request should be accepted
   And the response should include :
"""
item:
  location: 2/.12
  latitude: 2
  longitude: .12
"""
   And there should be 1 item in the database


# Why not, in the future ? Easy enough to add a custom provider for this.
#Scenario: US-style coordinates
#  When I give the following item :
#"""
#location: 41°41'39.0"N 0°09'24.0"W
#"""
#  Then the request should be accepted
#   And the response should include :
#"""
#item:
#  location: 2/.12
#  latitude: 2
#  longitude: .12
#"""
#   And there should be 1 item in the database


@geocode
Scenario: Give an item with only a postal address location
  When I give the following item :
"""
location: 66 Avenue des Champs-Élysées, 75008 Paris
"""
  Then the request should be accepted
   And the response should include :
"""
item:
  location: 66 Avenue des Champs-Élysées, 75008 Paris
  latitude: 48.8708484
  longitude: 2.3053611
"""
   And there should be 1 item in the database


@geocode
# This scenario will randomly fail and locate this IP in Portugal ?
# It does not matter much, as you should run behat with --tags=~geocode
# We should probably make a test with IPv6 too/instead.
Scenario: Give an item with only an IP address location (may fail)
  When I give the following item :
"""
location: 82.241.251.185
"""
  Then the request should be accepted
   And the response should include :
"""
item:
  location: 82.241.251.185
  latitude: 43.604
  longitude: 1.444
"""
   And there should be 1 item in the database


## TITLE AND TAGS ##############################################################


Scenario: Give an item with a location, a title, a description, and tags
  Given there is a tag named "book"
    And there is a tag named "pristine"
   When I give the following item :
"""
location: 48.8708484/2.3053611
title: Alice in Wonderland
description: |
  Slightly foxed, good book.
  Lots of madness, and hats.
tags:
  - book
  - pristine
"""
   Then the request should be accepted
    And the response should include :
"""
item:
  author:
    username: goutte
  title: Alice in Wonderland
  description: |
    Slightly foxed, good book.
    Lots of madness, and hats.
  tags:
    - book
    - pristine
"""
    And there should be 1 item in the database


Scenario: Give an item with a title with special characters
   When I give the following item :
"""
location: 48.8708484, 2.3053611
title: Planche de chêne
description: |
  %~#éàç_-*+✇
"""
   Then the request should be accepted
    And the response should include :
"""
item:
  title: Planche de chêne
  description: |
    %~#éàç_-*+✇
"""
    And there should be 1 item in the database


Scenario: Give an item with a very long title that gets truncated
   When I give the following item :
"""
location: 48.8708484, 2.3053611
title: I am longer than 32 characters ❤ Just because. Yep.
"""
   Then the request should be accepted
    And the response should include :
"""
item:
  title: I am longer than 32 characters ❤
"""
    And there should be 1 item in the database


Scenario: Give an item with tags, and ignore non-existing tags
  Given there is a tag named "wood"
   When I give the following item :
"""
location: 48.8708484, 2.3053611
tags:
  - wood
  - nope
"""
   Then the request should be accepted
    And the response should include :
"""
item:
  tags:
    - wood
"""
    And the response should not include :
"""
item:
  tags:
    - nope
"""
    And there should be 1 item in the database


## KARMA ##################################################################


Scenario: Give items and earn some karma points
  When I give the following item :
"""
location: 48.8708484, 2.3053611
"""
  Then the request should be accepted
   And the response should include :
"""
karma: 3
"""
   And there should be 1 item in the database
   And the user Goutte should have 3 karma points
  Then I give the following item :
"""
location: 48.8708484, 2.3053611
title: This thing
"""
  Then the request should be accepted
   And the response should include :
"""
karma: 4
"""
   And the user Goutte should have 7 karma points


## QUOTAS ######################################################################

# It's possible to level up from 0 to 1 by simply adding two items.
# It's enjoyable to be able to level up on the first day !
# But you can only do it if you're providing enough information about the items.

# IMPORTANT -- PAPER CUT
# About the step "I gave <N> items <time> ago"
# It is a HACK that does NOT update the user's karma because it bypasses the API
# to directly write in the database.
# It could be fixed to use the API first and then manually update the created_at
# fields of added items, so that this does not happen, but then it would take a
# LONG time when N is big. Also, I'm too lazy|hurried to fix this now.

@quotas
Scenario: Level up from 0 to 1 in one day
 Given I am level 0
   And there is a tag named "moop"
   And there is a tag named "lost"
  Then I should be level 0
  When I give the following item :
"""
location: 43.596017, 1.437586
title: 17 old shoes
tags:
  - moop
"""
   And I give the following item :
"""
location: 43.596018, 1.437586
title: Silk glove
tags:
  - lost
"""
# Don't use that step here, it's a hack that does not update karma.
#   And I gave 2 items 1 minute ago
  Then I should be level 1
  Then there should be 2 items in the database
  When I try to give another item
  Then the request should be accepted
   And there should be 3 items in the database
# Because my quotas are now level 1, not 0


@quotas
Scenario: Fail to exceed level 1 daily quota of 4
 Given I am level 1
   And I already gave 4 items 1 minute ago
  Then there should be 4 items in the database
  When I try to give an item
  Then the request should not be accepted
   And there should be 4 items in the database


@quotas
Scenario: Fail to exceed level 9 daily quota of 20
 Given I am level 9
   And I already gave 19 items 12 hours ago
  Then there should be 19 items in the database
  When I try to give an item
  Then the request should be accepted
   And there should be 20 items in the database
  When I try to give an item
  Then the request should not be accepted
   And there should be 20 items in the database


@quotas
Scenario: Quotas are daily
 Given I am level 1
   And I already gave 4 items 25 hours ago
  Then there should be 4 items in the database
  When I try to give an item
  Then the request should be accepted
   And there should be 5 items in the database