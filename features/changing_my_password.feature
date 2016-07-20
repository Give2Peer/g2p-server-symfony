@rest
Feature: Changing my password
  In order to set a password I'll remember and secure my account
  As a user
  I want to (be able to) change my password



Scenario: Changing my password
  Given I am the registered user named Shibby
    And my password is "tralalaboudin"
   Then I should succeed to authenticate with password "tralalaboudin"
    And I should fail to authenticate with password "plop"
   When I change my password to "plop"
   Then the request should be accepted
    And I should fail to authenticate with password "no plop"
    And I should succeed to authenticate with password "plop"
