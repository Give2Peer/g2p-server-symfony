@rest
Feature: Giving items
  In order to bring joy
  As a nice being
  I want to give items for free


Background:
  Given I am the registered user named "Goutte"



Scenario: Give an item without a location
  When I give the following :
"""
nope: I'm not going to tell you where it is !
"""
  Then the request should not be accepted
   And there should be 0 items in the database



@geocode
Scenario: Give an item with an ungeolocalizable location
  When I give the following :
"""
location: The ass-end of nowhere !
"""
  Then the request should not be accepted
   And there should be 0 items in the database



Scenario: Give an item with only a latitude/longitude location
  When I give the following :
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
  When I give the following :
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
  When I give the following :
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



@geocode
Scenario: Give an item with only a postal address location
  When I give the following :
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
Scenario: Give an item with only an IP address location
  When I give the following :
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



Scenario: Give an item with a location, a title, a description, and tags
  Given there is a tag named "book"
    And there is a tag named "pristine"
   When I give the following :
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
  giver:
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



Scenario: Give an item with a location and a title with special characters
   When I give the following :
"""
location: 48.8708484, 2.3053611
title: Planche de chêne
description: |
  %~#éàç_-*+
"""
   Then the request should be accepted
    And the response should include :
"""
item:
  title: Planche de chêne
  description: |
    %~#éàç_-*+
"""
    And there should be 1 item in the database



Scenario: Give an item with a location, tags, and ignore non-existing tags
  Given there is a tag named "wood"
   When I give the following :
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



Scenario: Give items and earn some experience points
  When I give the following :
"""
location: 48.8708484, 2.3053611
"""
  Then the request should be accepted
   And the response should include :
"""
experience: 3
"""
   And there should be 1 item in the database
   And the user Goutte should have 3 experience points
  Then I give the following :
"""
location: 48.8708484, 2.3053611
title: This thing
"""
  Then the request should be accepted
   And the response should include :
"""
experience: 4
"""
   And the user Goutte should have 7 experience points



@wip
Scenario: Fail to exceed daily quota in giving items (level 1)
 Given I am level 1
  When I give the following :
"""
location: 48.8708484, 2.3053611
"""
  Then the request should be accepted
   And there should be 1 item in the database
  When I give the following :
"""
location: 48.8708484, 2.3053611
"""
  Then the request should be accepted
  When I give the following :
"""
location: 48.8708484, 2.3053611
"""
  Then the request should not be accepted