postgreSQL
==========

We need the sweet, Ô so sweet sugar that the `earthdimensions` extension provides for pgSQL.
It allows us to estimate great circle distances between locations within our database queries,
so that we can very rapidly check for items around the location of the client.

We use `earth_distance` and `ll_to_earth`, specifically.

To install pgSQL, look at :

http://stackoverflow.com/questions/10588646/how-to-change-a-database-to-postgresql-with-symfony-2-0

And don't forget (for each database!) :

psql give2peer
CREATE EXTENSION cube;
CREATE EXTENSION earthdistance;
psql give2peer_test
CREATE EXTENSION cube;
CREATE EXTENSION earthdistance;


Fixtures
--------

Fake it 'til you make it !

We provide some dummy content to load into your database, which may also be a
good entrypoint for your import script / content if you already have some.

Look at `Give2Peer\Give2PeerBundle\DataFixtures\ORM\LoadFakeData`.

Load the fixtures with :

```
$ app/console doctrine:fixtures:load --env=test
```

Note that the feature suite does not use these fixtures, it makes up its own.


BLACKBOARD
==========

Works:
SELECT i0_.id AS id0, i0_.location AS location1, i0_.latitude AS latitude2, i0_.longitude AS longitude3, i0_.title AS title4,
       i0_.description AS description5, i0_.createdAt AS createdat6, i0_.updatedAt AS updatedat7,
       earth_distance(ll_to_earth(i0_.latitude, i0_.longitude),ll_to_earth(?, ?)) AS sclr8,
       i0_.giver_id AS giver_id9, i0_.spotter_id AS spotter_id10, i0_.owner_id AS owner_id11
FROM Item i0_
WHERE earth_distance(ll_to_earth(i0_.latitude, i0_.longitude),ll_to_earth(?, ?))
<= ? [43.579909,1.467469,43.579909,1.467469,10000] []

