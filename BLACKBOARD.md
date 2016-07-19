Blackboard
==========

All of the REST API is done "by hand", and supports only JSON and HTTP auth. It's not good.
We should probably move to a better way of doing APIs, like using :
https://github.com/dunglas/DunglasApiBundle
or
https://github.com/api-platform/api-platform (<3)


Optimizations
=============

Add classes that always will be autoloaded, but no annotations !
http://symfony.com/doc/current/cookbook/bundles/extension.html#adding-classes-to-compile

But if I `use` a class but never actually use it, is there a file read ?

Ideas:
- Response/*
- Actually that's pretty much it.


RueCup
======

Contacted them. No answer yet, months later. Maybe I was too hyped ?
Should've waited a few hours at least for excitement to power down...

Anyhow that's what they're using:

Categories
- Ameublement
- Bois & Matériaux
- Electromenager
- Vêtements & Textile
- Divers & Autres


États
- Bon état
- Abîmé
- Réparable
- Inconnu

Every single person I've told this about told me that we should handle
more than just repairable fixtures. But it's a great name and a great
community, and it's specialized ! We really need to chaaaaaaaaaaaaat !

I'm so forever alone :/
