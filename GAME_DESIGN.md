
Everything in this file is an EARLY DRAFT.
It is absolutely open to discussion !


About
=====

The game design's **primary purpose** here is to **tackle abusive users.**

We might as well make it fun and provide rewards and recognition for positive and constructive behavior.

Ideally, we should rely on good faith and allow users to give and remove themselves karma when they feel like it. And unto others, too. What a beautiful mess that would be...

In practice, because there's no `check_good_faith(user)` function yet that _spammers_ and _trolls_ cannot fool, the service will by itself provide karma on usage.
We plan on many ways to enable users to give karma to one another, too. Later, though.


Karma
=====

Basically just _experience points_.
Maybe we'll need in the future to separate karma and experience. (StackOverflow-like)

Anyhow, right now, karma is gained by using the service :

- Query the service (1 point per day)
- Publish an item (3 points)  <-- that's a lot, maybe 2 ?
  - with a title (1 extra point)
  - with a description (1 extra point)
  - with tags (1 extra point)

_(many more things!)_


Abilities
---------

Here are the unlocked abilities, per level.

Level 000 : look at the map, add new items
Level 002 : access statistics activity (todo)
Level 005 : delete own items (todo)
Level 0?? : delete others items (todo) (requires some form of moderation)
Level 0?? : suggest a new tag
Level 0?? : create a new tag


Quotas
------

Experience points allow you to *level up*, and some *daily* action quotas
increase with your level, such as :

- Query items (20 more queries per level, starting at 30)
- Publish items (2 more publications per level, starting at 2) (maybe 1 more per level ?)
