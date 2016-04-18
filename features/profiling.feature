@rest
@profile
Feature: Profiling
  In order to display the user's profile
  As a client
  I need to get that information from the server

# Possibly two features, one for own profile, one for other's profiles.

Scenario: Fail to get my profile information when not authenticated
  Given I am not authenticated
   When I request my profile information
   Then the request should not be accepted


Scenario: Get information about myself
  Given I am a user named Goutte
   When I request my profile information
   Then the request should be accepted
    And the response should include :
"""
user:
    username: goutte
    karma: 0
    level: 0
"""
# Because I (and only I) should see my own karma points.

@glop
Scenario: Get information about my items
  Given I am a user named Goutte
    And I gave the following item 5 minutes ago :
"""
location: 48.8708484/2.3053611
title: Alice in Wonderland
"""
    And I gave the following item 1 minute ago :
"""
location: 48.8708484/2.3053611
title: More Recent Gift
"""
   When I request my profile information
   Then the request should be accepted
    And the response should include :
"""
items:
    -
        title: More Recent Gift
    -
        title: Alice in Wonderland
"""
# Because the list of items should be sorted by reverse publication order.


Scenario: Get another user's public profile information
  Given I am a user named Goutte
    And there is a user named Migi
   When I request the profile information of Migi
   Then the request should be accepted
    And the response should include :
"""
user:
    username: migi
    level: 0
"""
    But the response should not include :
"""
user:
    karma: 0
"""
# Because I should not see other people's karma points.
