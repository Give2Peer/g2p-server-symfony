@rest
@registering
Feature: Pre-registering
  In order to authenticate without asking my user to register first
  As a client
  I need to automatically pre-register a new user account


Scenario: Pre-register
   When I pre-register
   Then the request should be accepted
    And the response should include :
"""
user:
    id: 1
"""
# And also generated username: and password:
# Look at the result of the following step
    And I dump the response
    And there should be 1 user in the database


# Psychologists think they're experimental psychologists.
# Experimental psychologists think they're biologists.
# Biologists think they're biochemists.
# Biochemists think they're chemists.
# Chemists think they're physical chemists.
# Physical chemists think they're physicists.
# Physicists think they're theoretical physicists.
# Theoretical physicists think they're mathematicians.
# Mathematicians think they're metamathematicians.
# Metamathematicians think they're philosophers.
# Philosophers think they're gods.
