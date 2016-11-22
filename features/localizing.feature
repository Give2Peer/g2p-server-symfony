@l10n
Feature: L10N
  In order to provide content to clients in the language of their choosing
  As a server
  I want to support localization


Scenario: English
  Given I am the user named John of level 1
   When I greet the server in "en"
   Then the request should be accepted
    And the response should contain "Welcome!"


Scenario: French
  Given I am the user named Léon of level 1
   When I greet the server in "fr"
   Then the request should be accepted
    And the response should contain "Bonjour !"


Scenario: Unsupported languages are ignored
  Given I am the user named Idiot of level 1
   When I greet the server in "newspeak, fr"
   Then the request should be accepted
    And the response should contain "Bonjour !"


Scenario: Multiple languages with explicit priorities
  Given I am the user named Goutte of level 1
   When I greet the server in "en ; q=0.9, fr ; q=1.0"
   Then the request should be accepted
    And the response should contain "Bonjour !"


Scenario: Country locales variants
  Given I am the user named Chauvin of level 1
   When I greet the server in "fr_FR"
   Then the request should be accepted
    And the response should contain "Bonjour !"
