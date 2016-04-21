@rest
@delete
Feature: Deleting items
  In order to manually clean up the server or drastically fix mistakes
  As a user
  I want to be able to delete items


@wip
Scenario: Delete my own item
  Given I am the registered user named "Lucie"
    And I am level 3
    And my quota for adding items is 8
    And I gave the following item :
"""
location: 43.590226, 1.432487
title: Paroles de l'âme au vent
description: |
  « on va réchauffer le monde...
  à froid
  Des fois les rois sont ceux
  que l'on ne voit pas »
"""
  Then I should be the author of 1 item
   And I dump myself
   And my quota for adding items should be 7
   And there should be an item titled "Paroles de l'âme au vent"
   And that item should be shown on the map around 43.590226, 1.432487
  When I try to delete the item titled "Paroles de l'âme au vent"
  Then the request should be accepted
   But I should be the author of 0 item
   And there should still be an item titled "Paroles de l'âme au vent"
   And that item should be marked for deletion
   And that item should not be shown on the map around 43.590226, 1.432487
