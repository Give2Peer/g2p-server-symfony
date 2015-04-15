@rest
@geocode
Feature: Giving items
  In order to bring joy
  As a nice being
  I want to give items for free


Background:
  Given I am the registered user named "Goutte"



Scenario: Give an item without a location
  When I POST to /give the following :
"""
nope: I'm not going to tell you where it is !
"""
  Then the request should not be accepted
   And there should be 0 items in the database



Scenario: Give an item with an ungeolocalizable location
  When I POST to /give the following :
"""
location: The ass-end of nowhere !
"""
  Then the request should not be accepted
   And there should be 0 items in the database



Scenario: Give an item with only a latitude/longitude location
  When I POST to /give the following :
"""
location: -1.441/43.601
"""
  Then the request should be accepted
   And the response should include :
"""
location: -1.441/43.601
latitude: -1.441
longitude: 43.601
"""
   And there should be 1 item in the database



Scenario: Give an item with only a latitude, longitude location
  When I POST to /give the following :
"""
location: -1.441, 43.601
"""
  Then the request should be accepted
   And the response should include :
"""
location: -1.441, 43.601
latitude: -1.441
longitude: 43.601
"""
   And there should be 1 item in the database



Scenario: Give an item with weird latitude/longitude location
  When I POST to /give the following :
"""
location: 2/.12
"""
  Then the request should be accepted
   And the response should include :
"""
location: 2/.12
latitude: 2
longitude: .12
"""
   And there should be 1 item in the database



Scenario: Give an item with only a postal address location
  When I POST to /give the following :
"""
location: 66 Avenue des Champs-Élysées, 75008 Paris
"""
  Then the request should be accepted
   And the response should include :
"""
location: 66 Avenue des Champs-Élysées, 75008 Paris
latitude: 48.8708484
longitude: 2.3053611
"""
   And there should be 1 item in the database



Scenario: Give an item with only an IP address location
  When I POST to /give the following :
"""
location: 82.241.251.185
"""
  Then the request should be accepted
   And the response should include :
"""
location: 82.241.251.185
latitude: 43.604
longitude: 1.444
"""
   And there should be 1 item in the database



Scenario: Give an item with a location, a title, a description, and tags
  Given there is a tag named "book"
    And there is a tag named "pristine"
   When I POST to /give the following :
"""
location: 66 Avenue des Champs-Élysées, 75008 Paris
title: Alice in Wonderland
description: |
  Slightly foxed, good story.
  Lots of madness, and hats.
tags:
  - book
  - pristine
"""
   Then the request should be accepted
    And the response should include :
"""
giver:
  username: goutte
location: 66 Avenue des Champs-Élysées, 75008 Paris
latitude: 48.8708484
longitude: 2.3053611
title: Alice in Wonderland
description: |
  Slightly foxed, good story.
  Lots of madness, and hats.
tags:
  - book
  - pristine
"""
    And there should be 1 item in the database



Scenario: Give an item with a location and ignore non-existing tags
  Given there is a tag named "wood"
   When I POST to /give the following :
"""
location: 66 Avenue des Champs-Élysées, 75008 Paris
tags:
  - wood
  - nope
"""
   Then the request should be accepted
    And the response should include :
"""
tags:
  - wood
"""
    And the response should not include :
"""
tags:
  - nope
"""
    And there should be 1 item in the database