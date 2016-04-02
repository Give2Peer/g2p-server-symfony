@rest
Feature: Registering
  In order to use the REST API behind the HTTP authentication
  As a client
  I need to automatically register a new user account



Scenario: Register and receive confirmation
   When I register the following :
"""
username: Goutte
password: hO5viH4XkkPbRtuPlop=
email:    goutte@give2peer.org
"""
   Then the request should be accepted
    And the response should include :
"""
id: 1
username: goutte
"""
    And there should be 1 user in the database



Scenario: Fail to register with an already taken username
  Given there is a user named "Goutte"
   When I register the following :
"""
username: Goutte
password: hO5viH4XDkPbRtuPlop=
email:    goutte@give2peer.org
"""
   Then the request should not be accepted
    And the response should include :
"""
error:
    code: 1
"""
    And there should be 1 user in the database



Scenario: Fail to register with an already taken username (lowercase matters)
  Given there is a user named "Goutte"
   When I register the following :
"""
username: GouTTE
password: hO5viHXDkkPbRtuPlop=
email:    goutte@give2peer.org
"""
   Then the request should not be accepted
    And the response should include :
"""
error:
    code: 1
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
   Then the request should not be accepted
    And the response should include :
"""
error:
    code: 7
"""
    And there should be 1 user in the database
