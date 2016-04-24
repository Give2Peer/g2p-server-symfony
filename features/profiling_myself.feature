@rest
@user
@profile
Feature: Profiling myself
  In order to "know thyself"
  As a user
  I want to have all the information about myself, and some more

#  In order to display my user's profile
#  As a client
#  I need to get that information from the server



Scenario: Fail to get my profile information when not authenticated
  Given I am not authenticated
   When I request my profile information
   Then the request should be denied



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



Scenario: Get information about my items
  Given I am a user named Goutte
    And I am level 27
    And I gave the following item 5 hours ago :
"""
location: 48.8708484/2.3053611
title: Alice in Wonderland
"""
    And I gave the following item 42 minutes ago :
"""
location: 43.758035/4.503676
title: La Horde de Contrevent
"""
    And I gave the following item 1 minute ago :
"""
location: 44.210891/4.013791
title: Gift I just made
"""
   When I request my profile information
   Then the request should be accepted
    And the response should include :
"""
items:
    -
        title: Gift I just made
    -
        title: La Horde de Contrevent
    -
        title: Alice in Wonderland
"""
# Because the list of items should be ordered by reverse `updated at` time.


