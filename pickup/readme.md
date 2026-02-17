# Foodsoft Pickup App

Intended to be installed at https://pickup.foodcoops.at/

requires an additional foodsoft API controller: https://github.com/foodcoopsat/foodsoft/pull/17/

## Activation for Foodcoops
For each foodcoop, a copy of the `template-foodcoop` directory has to be generated and named with the foodcoop's name identically like in the foodsoft url https://app.foodcoops.at/(fc-name). The permissions of the directory have to be `0777` te enable the php-daemon to make directories and write data.
The app can then be called via https://pickup.foodcoops.at/(fc-name)

The index.php file in this folder has to contain the oauth access credentials for the foodsoft of the foodcoop and optional configuration parameters.

## Local Test
The app can be tested in combination with a local foodsoft installation.
