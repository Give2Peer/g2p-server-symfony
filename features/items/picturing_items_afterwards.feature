@rest
@pic
Feature: Picturing items afterwards
  In order to describe faster than with a thousand words
  As a tagger
  I need to attach pictures to existing items


# /!\ WARNING
# We are spec'ing the path `web/item_picture_test` but in production the actual
# path to pictures is simply `web/item_picture`.
# Tests use another path so that we can empty the directory between scenarios.
# It would be too dangerous to use the same path for both the test and prod
# environments, as simply running the test suite would wreak havoc.


Background:
  Given I am the registered user named Goutte
    And I gave an item titled "Coffee Grinder"
    And I gave another item
    And there is an item at 43.566591, 1.474969



Scenario: Attach a JPG picture to an item
  Given there should not be a file at web/item_picture_test/1.jpg
   When I POST to /item/2/picture the file features/assets/dummy.jpg
   Then the request should be accepted
    And the response should include :
"""
item:
    pictures:
        - url: http://localhost/item_picture_test/1.jpg
          thumbnails:
              240x240: http://localhost/item_picture_test/1_240x240.jpg
"""
    And there should be a file at web/item_picture_test/1.jpg
    And there should be a file at web/item_picture_test/1_240x240.jpg



Scenario: Attach multiple JPG pictures to an item
  Given there should not be a file at web/item_picture_test/1.jpg
   When I POST to /item/2/picture the file features/assets/dummy.jpg
   Then the request should be accepted
   When I POST to /item/2/picture the file features/assets/dummy.jpg
   Then the request should be accepted
    And the response should include :
"""
item:
    pictures:
        - url: http://localhost/item_picture_test/1.jpg
        - url: http://localhost/item_picture_test/2.jpg
"""
    And there should be a file at web/item_picture_test/1.jpg
    And there should be a file at web/item_picture_test/2.jpg



## OTHER FILE TYPES ############################################################

Scenario: Attach a PNG picture to an item
   When I POST to /item/2/picture the file features/assets/dummy.png
   Then the request should be accepted
    And there should be a file at web/item_picture_test/1.jpg
    And there should be a file at web/item_picture_test/1_240x240.jpg


Scenario: Attach a GIF picture to an item
   When I POST to /item/2/picture the file features/assets/dummy.gif
   Then the request should be accepted
    And there should be a file at web/item_picture_test/1.jpg
    And there should be a file at web/item_picture_test/1_240x240.jpg


# The generated thumbnail has wrong colors, though.
# I guess PHP and/or GD is not WebP-ready yet.
Scenario: Attach a WebP picture to an item (buggy)
   When I POST to /item/2/picture the file features/assets/dummy.webp
   Then the request should be accepted
    And there should be a file at web/item_picture_test/1.jpg
    And there should be a file at web/item_picture_test/1_240x240.jpg



## DELETION ####################################################################

Scenario: Delete the picture files when an Item is deleted
  Given there should not be a file at web/item_picture_test/1.jpg
   When I POST to /item/1/picture the file features/assets/dummy.jpg
   Then the request should be accepted
    And there should be a file at web/item_picture_test/1.jpg
    And there should be a file at web/item_picture_test/1_240x240.jpg
   When the item titled "Coffee Grinder" is hard deleted
   Then there should not be a file at web/item_picture_test/1.jpg
    And there should not be a file at web/item_picture_test/1_240x240.jpg



## FAILURES ####################################################################

# malicious.jpg contains PHP code, not image data
# $ file --mime-type -b features/assets/malicious.jpg
# text/x-php
Scenario: Fail to attach a malicious JPG picture to an item
   When I POST to /item/2/picture the file features/assets/malicious.jpg
   Then the request should be denied
    And there should not be a file at web/pictures_test/1/1.jpg


Scenario: Fail to attach a picture to a non-existent item
   When I POST to /item/42/picture the file features/assets/dummy.jpg
   Then the request should be denied
    And there should not be a file at web/item_picture_test/1.jpg
    And there should not be a file at web/item_picture_test/1_240x240.jpg


Scenario: Fail to attach a picture to an item you did not create yourself
   When I POST to /item/3/picture the file features/assets/dummy.jpg
   Then the request should be denied
    And there should not be a file at web/item_picture_test/1.jpg

