#!/bin/bash

app/console doctrine:schema:drop --force --env=test
app/console doctrine:schema:create --env=test
