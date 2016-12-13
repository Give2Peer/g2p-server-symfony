@rest
Feature: Changing my username
  In order to set a username I'll remember and secure my account
  As a user
  I want to (be able to) change my username



Scenario: Changing my username
  Given I am the registered user named Wise Beardy Gnu 007
   Then there should be a user named Wise Beardy Gnu 007
    And there should not be a user named Richard Stallman
   When I change my username to Richard Stallman
   Then the request should be accepted
    And there should not be a user named Wise Beardy Gnu 007
    And there should be a user named Richard Stallman
