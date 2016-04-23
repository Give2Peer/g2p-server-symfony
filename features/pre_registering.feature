@rest
@registering
Feature: Registering
  In order to authenticate without asking my user to register first
  As a client
  I need to automatically pre-register a new user account


# keys MUST be URL-safe, say match [a-zA-Z0-9._=-]
Scenario: Pre-register
   When I pre-register
   Then the request should be accepted
    And the response should include :
"""
user:
    id: 1
"""
    And I dump the response
    And there should be 1 user in the database

#Scenario: Fail to pre-register with wrong keys
#   When I try to pre-register with the key "NotTheCorrectLength"
#   Then the request should be denied
#   When I try to pre-register with the key "àé__4567890123456789012345678901"
#   Then the request should be denied
#  Given there is a user named Goutte
#    And Goutte has the pre-registration key "01234567890123456789012345678901"
#   When I try to pre-register with the key "01234567890123456789012345678901"
#   Then the request should be denied

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
