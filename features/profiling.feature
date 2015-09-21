@rest
Feature: Profiling
  In order to display the user's profile
  As a client
  I need to get that information from the server


Scenario: Fail to get my profile information when lot logged in
   When I get /profile
   Then the request should not be accepted


Scenario: Get my own profile information
  Given I am the user named "Goutte"
   When I get /profile
   Then the request should be accepted
    And the response should include :
"""
user:
    username: goutte
    experience: 0
    level: 1
"""


Scenario: Get another user's public profile information
  Given I am the user named "Goutte"
    And there is a user named "Migi"
   When I get /profile/migi
   Then the request should be accepted
    And the response should include :
"""
user:
    username: migi
    level: 1
"""
    And the response should not include :
"""
user:
    experience: 0
"""
