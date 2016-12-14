@rest
Feature: Getting the details of an item
  In order to make sure an item interests me
  As a gatherer
  I need to view the details of an item



Background:
  Given I am the registered user named "黒滴"
    And there is a tag named "broken"
    And there is a tag named "dirty"



# The title is optional, but it's convenient to set it in order to easily
# retrieve the item by its title in later steps.
Scenario: Get the details of a minimalist item without a picture
  Given I gave the following item :
"""
title: Test
location: 43.566591, 1.474969
"""
   When I get the details of the item titled "Test"
   Then the request should be accepted
    And the response should include :
"""
item:
    title: Test
    latitude: 43.566591
    longitude: 1.474969
    description:
    pictures: []
    tags: []
"""



Scenario: Get the details of a detailed item without a picture
  Given I gave the following item :
"""
title: Test
description: A handy description !
location: 43.566591, 1.474969
tags:
    - broken
"""
   When I get the details of the item titled "Test"
   Then the request should be accepted
    And the response should include :
"""
item:
    title: Test
    latitude: 43.566591
    longitude: 1.474969
    description: A handy description !
    pictures: []
    tags:
        - broken
"""



Scenario: Get the details of a detailed item
  Given I pre-uploaded the image file features/assets/dummy.jpg
    And I gave the following item :
"""
title: Soul
description: A handy description !
location: 43.566591, 1.474969
pictures:
    - 1
tags:
    - broken
    - dirty
"""
   When I get the details of the item titled "Soul"
   Then the request should be accepted
    And the response should include :
"""
item:
    title: Soul
    latitude: 43.566591
    longitude: 1.474969
    description: A handy description !
    pictures:
        - id: 1
          url: http://localhost/item_picture_test/1.jpg
    tags:
        - broken
        - dirty
"""



