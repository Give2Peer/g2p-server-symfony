@rest
@wip
Feature: Registration
  In order to use the REST API behind HTTP authentication
  As a client
  I need to automatically register a new user account



Scenario: Register and recover credentials
   When I POST to /register the following :
"""
username: Goutte
password: hO5vP=
"""
   Then the request should be accepted
    And there should be 1 user in the database



Scenario: Try to register with an already taken username
  Given there is a user named "Goutte"
   When I POST to /register the following :
"""
username: Goutte
password: hO5vP=
"""
   Then the request should not be accepted
    And the response should include :
"""
error:
    code: 1
"""
    And there should be 1 user in the database
