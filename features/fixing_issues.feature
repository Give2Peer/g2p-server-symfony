@bug
Feature: Fixing Issues
  In order to definitely fix issues
  As a developer
  I want to describe scenarios that failed in the past


# Not all features make sense to be written, there would be too many.
# When there's a Scenario that is too specific to be put in a Feature
# but we used it to expose and squash a bug, we may write it here.


Scenario: Do not fail to hard-delete an item reported as abusive
  Given I am the user named Goutte of level 1
    And there is a user named Kiouze of level 5
    And Kiouze added without karma gain an item titled "SGB"
    And I report the item titled "SGB" as abusive
   Then my request should be accepted
   When the item titled "SGB" is hard deleted
   Then it should not raise an exception
