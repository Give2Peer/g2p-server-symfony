<?php

// As-is, this does not work.
// I don't know how to autoload easily.
// Maybe a python script instead ? import ftw


use \Symfony\Component\Yaml\Yaml;
$path = __DIR__ . DIRECTORY_SEPARATOR;
$animals = Yaml::parse(file_get_contents($path));

print_r($animals);