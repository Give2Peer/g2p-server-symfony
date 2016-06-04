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



Scenario: Attach a JPG picture to an item
   When I POST to /item/1/picture the file features/assets/dummy.jpg
   Then the request should be accepted
    And there should be a file at web/pictures_test/1/1.jpg
    And there should be a file at web/pictures_test/1/thumb.jpg


Scenario: Attach a PNG picture to an item
   When I POST to /item/1/picture the file features/assets/dummy.png
   Then the request should be accepted
    And there should be a file at web/pictures_test/1/1.png
    And there should be a file at web/pictures_test/1/thumb.jpg


Scenario: Attach a GIF picture to an item
   When I POST to /item/1/picture the file features/assets/dummy.gif
   Then the request should be accepted
    And there should be a file at web/pictures_test/1/1.gif
    And there should be a file at web/pictures_test/1/thumb.jpg


# The generated thumbnail has wrong colors, though.
# I guess PHP and/or GD is not WebP-ready yet.
Scenario: Attach a WebP picture to an item (buggy)
   When I POST to /item/1/picture the file features/assets/dummy.webp
   Then the request should be accepted
    And there should be a file at web/pictures_test/1/1.webp
    And there should be a file at web/pictures_test/1/thumb.jpg


# malicious.jpg contains PHP code, not image data
# $ file --mime-type -b features/assets/malicious.jpg
# text/x-php
Scenario: Fail to attach a malicious JPG picture to an item
   When I POST to /item/1/picture the file features/assets/malicious.jpg
   Then the request should be denied
    And I dump the response
    And there should not be a file at web/pictures_test/1/1.jpg


Scenario: Fail to attach a picture to a non-existent item
   When I POST to /item/42/picture the file features/assets/dummy.jpg
   Then the request should be denied
    And I dump the response
    And there should not be a file at web/pictures_test/1/1.jpg


Scenario: Fail to attach a picture to a non-authorized item
   When I POST to /item/2/picture the file features/assets/dummy.jpg
   Then the request should be denied
    And I dump the response
    And there should not be a file at web/pictures_test/2/1.jpg

