#!/usr/bin/env bash

# Install WordPress
npm run --prefix wordpress env:start
sleep 10
npm run --prefix wordpress env:install