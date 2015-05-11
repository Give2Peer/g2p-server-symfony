@rest
Feature: Picturing items
  In order to describe better than with a thousand words
  As a spotter or a giver
  I need to attach pictures to items

# /!\ WARNING
# This feature creates files in web/pictures that our suite does not delete.
# The test kernel needs to use test configuration and upload into pictures_test
# or something before we can safely automatize deletion of the uploaded files,
# because the danger is too great if by mishap the test suite is ran in prod.
# Right now it will REPLACE production files, so there is danger, but a lesser
# one as we only play around with the first item.

Background:
  Given I am the registered user named "Goutte"
    And I give an item at 43.578658, 1.468091
    And there is an item at 43.566591, 1.474969



Scenario: Attach a JPG picture
   When I POST to /picture/1 the file features/assets/1.jpg
   Then the request should be accepted
    And there should be a file at web/pictures/1/1.jpg
    And there should be a file at web/pictures/1/thumb.jpg


Scenario: Do not attach a PNG picture (for now)
   When I POST to /picture/1 the file features/assets/trollface.png
   Then the request should not be accepted
    And there should not be a file at web/pictures/1/1.png
    And there should not be a file at web/pictures/1/trollface.png


Scenario: Fail to attach a picture to a non-existent item
   When I POST to /picture/42 the file features/assets/1.jpg
   Then the request should not be accepted


Scenario: Fail to attach a picture to a non-authorized item
   When I POST to /picture/2 the file features/assets/2.jpg
   Then the request should not be accepted

