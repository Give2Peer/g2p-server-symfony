@rest
@user
Feature: Changing my email
  In order to set an email I own and really secure my account
  As a user
  I want to (be able to) change my email



Scenario: Changing my email
  Given I am the registered user named Shiva Ayyadurai
    And my email should not be anonymous@mail.org
   When I change my email to anonymous@mail.org
   Then the request should be accepted
    And my email should be anonymous@mail.org



Scenario: Failing to change my email to an already used email
  Given there is a user whose email is test@give2peer.org
  Given I am the registered user named Tester
    And my email should not be test@give2peer.org
   When I try to change my email to test@give2peer.org
   Then the request should be denied
    And my email should still not be test@give2peer.org
