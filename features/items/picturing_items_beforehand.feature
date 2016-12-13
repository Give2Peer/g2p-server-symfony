@rest
@pic
Feature: Picturing items beforehand
  In order to add items faster
  As a tagger
  I need to upload a picture while I'm still filling out the item form


# /!\ WARNING
# We are spec'ing the path `picture_test` but in production the actual path
# to pictures is simply `picture`. I don't know how to handle this better.
# Tests use another path so that we can empty the directory between scenarios.
# It would be too dangerous to use the same path for both the test and prod
# environments, as simply running the test suite would wreak havoc.
# See `app/config/config.yml` and `app/config/config_test.yml`.


Background:
  Given I am the registered user named Goutte


@wip
Scenario: Pre-upload a JPG picture
  Given there should not be a file at web/item_picture_test/1.jpg
   When I POST to /item/picture the file features/assets/dummy.jpg
   Then the request should be accepted
    And I dump the response
    And there should be a file at web/item_picture_test/1.jpg
    And there should be a file at web/item_picture_test/1_240x240.jpg
   When I POST to /item/picture the file features/assets/dummy.jpg
   Then the request should be accepted
    And there should be a file at web/item_picture_test/2.jpg
    And there should be a file at web/item_picture_test/2_240x240.jpg

