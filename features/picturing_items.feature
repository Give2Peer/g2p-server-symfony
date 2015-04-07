@rest
@wip
Feature: Picturing items
  In order to describe better than with a thousand words
  As a spotter or a giver
  I need to attach pictures to items

Background:
  Given I am the registered user named "Goutte"
    And there is an item at 43.578658, 1.468091
    And there is an item at 43.566591, 1.474969


# see https://github.com/Behat/Behat/issues/726
Scenario: Dummy scenario to skip behat's buggy behavior with first scenario
   When I do nothing
   Then nothing happens


Scenario: Attach a JPG picture
   When I POST to /picture/1 the file features/assets/1.jpg
   Then the request should be accepted
    And there should be a file at web/pictures/1/1.jpg


Scenario: Do not attach a PNG picture (for now)
   When I POST to /picture/1 the file features/assets/trollface.png
   Then the request should not be accepted
    And there should not be a file at web/pictures/1/1.png
    And there should not be a file at web/pictures/1/trollface.png

