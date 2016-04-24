@rest
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
