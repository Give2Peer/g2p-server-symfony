@rest
@delete
Feature: Deleting items
  In order to manually clean up the server or drastically fix mistakes
  As a user
  I want to be able to delete items


Scenario: Delete my own item
  Given I am the registered user named "Lucie"
    And I am level 3
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
   And I should have 1 item in my profile
   And there should be an item titled "Paroles de l'âme au vent"
   And that item should be shown on the map around 43.590226, 1.432487
  When I try to delete the item titled "Paroles de l'âme au vent"
  Then the request should be accepted
   But I should still be the author of 1 item
   And there should still be an item titled "Paroles de l'âme au vent"
   And that item should be marked for deletion
   And that item should not be shown on the map around 43.590226, 1.432487
   And I should have 0 items in my profile
