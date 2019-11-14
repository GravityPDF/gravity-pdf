#!/usr/bin/env bash

if [[ -z "$1" ]]; then
	echo "Missing Payload URL" 1>&2
	exit 1
fi

if [[ -z "$2" ]]; then
	echo "Missing output filename " 1>&2
	exit 1
fi

# Ensure the payload folder exists
mkdir -p dist/payload

# Basic Authentication
# $3 is a username/password combo
if [[ -z "$3" ]]; then
	curl -f -L $1 -o dist/payload/$2
else
  curl -f -u "$3" -L $1 -o dist/payload/$2
fi