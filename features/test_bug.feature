# This feature is for https://github.com/Behat/Behat/issues/726
@geocode
Feature: Expose a bug
  In order to expose an inconsistent behavior in behat
  As PHP traditionalist
  I need to print some foobar

Background:
  Given I print "Backround ran"

Scenario:
  Then I print "Foo"

Scenario:
  Then I print "Bar"