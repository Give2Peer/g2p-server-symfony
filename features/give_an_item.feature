@rest
Feature: Give an item
  In order to bring joy
  As a user
  I need to give items for free sometimes

Scenario: Give an item with only a location
  When I POST to /give the following :
"""
location: 66-68 Avenue des Champs-Élysées, 75008 Paris
"""
  Then the request should be accepted
   And there should be 1 item in the database