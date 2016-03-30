PostGreSQL
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

There's a script for that : `script/setup_pgsql`.


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



