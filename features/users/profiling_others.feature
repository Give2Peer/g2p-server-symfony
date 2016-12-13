@rest
@user
@profile
Feature: Profiling users
  In order to know others better
  As a user
  I want to have some information about them

#  In order to display a user profile
#  As a client
#  I need to get that information from the server



Scenario: Fail to get my profile information when not authenticated
  Given I am not authenticated
    And there is a user named Azazel
   When I request the profile information of the user named Azazel
   Then the request should be denied



Scenario: Fail to get the profile information of a nonexistent user
  Given I am a user named Lucifer
   When I request the profile information of the user #666
   Then the request should be denied



Scenario: Get another user's public profile information
  Given I am a user named Shin' Ichi
    And there is a user named Migi
   When I request the profile information of the user named Migi
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
# Or should I ?
# Right now we don't, because we can't go back from showing.
# Please chime in ! Vote will close when it will.
# In favor of showing : Goutte, ...
# In favor of not showing : ...
# In favor of censoring : it's the same, you know. (much fallacy such abuse wow)
# In favor of showing when you get level N : Goutte N=10, ...
# <add more>
