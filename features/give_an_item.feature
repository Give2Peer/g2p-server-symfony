@rest
Feature: Give an item
  In order to bring joy
  As a user
  I need to give items for free sometimes


Scenario: Give an item without a location
  When I POST to /give the following :
"""
nope: I'm not going to tell you where it is !
"""
  Then the request should not be accepted
   And there should be 0 items in the database


Scenario: Give an item with only a location
  When I POST to /give the following :
"""
location: 66-68 Avenue des Champs-Élysées, 75008 Paris
"""
  Then the request should be accepted
   And the response should include :
"""
location: 66-68 Avenue des Champs-Élysées, 75008 Paris
"""
   And there should be 1 item in the database


Scenario: Give an item with a location, a title and a description
  When I POST to /give the following :
"""
location: 66-68 Avenue des Champs-Élysées, 75008 Paris
title: Alice in Wonderland
description: |
  Slightly foxed, good story.
  Lots of madness, and hats.
"""
  Then the request should be accepted
   And the response should include :
"""
location: 66-68 Avenue des Champs-Élysées, 75008 Paris
title: Alice in Wonderland
description: |
  Slightly foxed, good story.
  Lots of madness, and hats.
"""
   And there should be 1 item in the database