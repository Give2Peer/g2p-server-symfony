@rest
@register
Feature: Registering
  In order to use my account on another device
  As a user
  I want to complete my pre-registration or register directly


#Scenario: Complete my pre-registration


Scenario: Register directly and receive confirmation
   When I register the following :
"""
username: Goutte
password: hO5viH4XkkPbRtuPlop=
email:    goutte@give2peer.org
"""
   Then the request should be accepted
    And the response should include :
"""
user:
    id: 1
    username: goutte
"""
    And there should be 1 user in the database



Scenario: Fail to register with an already taken username
  Given there is a user named Goutte
   When I register the following :
"""
username: Goutte
password: hO5viH4XDkPbRtuPlop=
email:    goutte@give2peer.org
"""
   Then the request should be denied
    And the response should include :
"""
error:
    code: api.error.user.username.taken
"""
    And there should be 1 user in the database



Scenario: Fail to register with an already taken username (lowercase matters)
  Given there is a user named Goutte
   When I register the following :
"""
username: GouTTE
password: hO5viHXDkkPbRtuPlop=
email:    goutte@give2peer.org
"""
   Then the request should be denied
    And the response should include :
"""
error:
    code: api.error.user.username.taken
"""
    And there should be 1 user in the database



Scenario: Fail to register with an already taken email
  Given there is a user with email "goutte@give2peer.org"
   When I register the following :
"""
username: Goutte
password: hO5viH4XDkPbRtuPlop=
email:    goutte@give2peer.org
"""
   Then the request should be denied
    And the response should include :
"""
error:
    code: api.error.user.email.taken
"""
    And there should be 1 user in the database
