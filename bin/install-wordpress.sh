#!/usr/bin/env bash

# Install WordPress
cd wordpress || exit
npm install dotenv wait-on
npm run env:start
sleep 10
npm run env:install
cd ..