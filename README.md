ammana
======

Generator for job protocols.

## Build status
[![Build Status](https://travis-ci.org/NoLegalTech/ammana.svg?branch=master)](https://travis-ci.org/NoLegalTech/ammana)
[![Build Status](https://travis-ci.org/NoLegalTech/ammana.svg?branch=pre)](https://travis-ci.org/NoLegalTech/ammana)
[![Build Status](https://travis-ci.org/NoLegalTech/ammana.svg?branch=dev)](https://travis-ci.org/NoLegalTech/ammana) (pro/pre/dev)

## Installation

### On a server

Your server needs to meet the following requirements:
 - php >= 7.1.8
 - mysql >= 5.5
 - git >= 2.1.4

Follow the next steps in your server (via ssh):
1. Clone the repository
2. If the server/path is intended for pre-production checkout the "pre" git branch
2. Create a mysql database
3. cp app/config/parameters.yml.dist app/config/parameters.yml
4. Edit app/config/parameters.yml to set up the database connection data

Follow the next steps in your local machine:
1. Clone the repository
2. cp deploy/parameters.yml.dist deploy/parameters.yml
3. Edit deploy/parameters.yml to set up the server connection data
4. Run "deploy/deploy pre" or "deploy/deploy pro" to deploy to pre-production or production respectively

NOTE: the deploy script NEVER deploys from your local copy, it takes the "pre" or "master" branches versions
from the repository to deploy to the server. If you need to deploy your own code version, fork the repository
so that you can have your own "pre" and "master" branches.


### For development

First you need to meet the following requirements:
 - php >= 7.1.8
 - mysql >= 5.5
 - git >= 2.1.4

Then follow the steps:
1. Clone the repository
2. Create a database in your local mysql
3. cp app/config/parameters.yml.dist app/config/parameters.yml
4. Edit app/config/parameters.yml to set up the database connection data
5. Run bin/rebuild_db

## Usage

### On a server

This project hasn't been released yet. It's under development.

### For development

Run bin/run

## Contributing

1. Fork it ( https://github.com/NoLegalTech/ammana/fork )
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create a new Pull Request
