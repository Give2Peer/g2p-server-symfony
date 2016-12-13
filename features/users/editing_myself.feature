@rest
@user
Feature: Updating my profile information
  In order to set an credentials I know and really secure my account
  As a user
  I want to (be able to) update my profile information



# In this file we're testing the API behind the awkward step :
# When I (try to )?edit my profile information with the following
# So don't replace it for another simpler step that may test a different API.
# (there are other steps that would look better, this is why I'm saying this)



Scenario: Changing my email only
  Given I am the registered user named Shiva Ayyadurai
    And my email should not be anonymous@mail.org
   When I update my profile information with the following :
"""
email: anonymous@mail.org
"""
   Then the request should be accepted
    And my email should be anonymous@mail.org



Scenario: Changing everything
  Given I am the registered user named Alpha
   Then there should not be a user named Beta
   When I update my profile information with the following :
"""
username: Beta
email: beta@ateb.mum
password: p455
"""
   Then the request should be accepted
   Then there should not be a user named Alpha
   Then there should be a user named Beta
   When I am the user named Beta
   Then my email should be beta@ateb.mum
    And I should succeed to authenticate with password "p455"



Scenario: Update all or none
  Given there is a user whose email is test@give2peer.org
    And I am the registered user named Batman
   Then there should not be a user named Joker
   When I try to update my profile information with the following :
"""
username: Joker
email: test@give2peer.org
"""
   Then the request should be denied
    And my email should still not be test@give2peer.org
    And there should not be a user named Joker
