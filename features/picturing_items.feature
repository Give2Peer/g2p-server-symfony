@rest
@pic
Feature: Picturing items
  In order to describe faster than with a thousand words
  As a tagger
  I need to attach pictures to items


# /!\ WARNING
# We are spec'ing the path `web/pictures_test` but in production the actual path
# to pictures is simply `web/pictures`. I don't know how to handle this yet.
# Tests use another path so that we can empty the directory between scenarios.
# It would be too dangerous to use the same path for both the test and prod
# environments, as running the simply running the test suite would wreak havoc.


Background:
  Given I am the registered user named Goutte
    And I give an item at 43.578658, 1.468091
    And there is an item at 43.566591, 1.474969



Scenario: Attach a JPG picture
   When I POST to /item/1/picture the file features/assets/1.jpg
   Then the request should be accepted
    And there should be a file at web/pictures_test/1/1.jpg
    And there should be a file at web/pictures_test/1/thumb.jpg


@tobreak
Scenario: Do not attach a PNG picture (for now)
   When I POST to /item/1/picture the file features/assets/trollface.png
   Then the request should be denied
    And there should not be a file at web/pictures_test/1/1.png
    And there should not be a file at web/pictures_test/1/trollface.png


Scenario: Fail to attach a picture to a non-existent item
   When I POST to /item/42/picture the file features/assets/1.jpg
   Then the request should be denied
    And there should not be a file at web/pictures_test/1/1.jpg


Scenario: Fail to attach a picture to a non-authorized item
   When I POST to /item/2/picture the file features/assets/2.jpg
   Then the request should be denied
    And there should not be a file at web/pictures_test/2/2.jpg

