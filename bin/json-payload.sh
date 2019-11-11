#!/usr/bin/env bash

if [[ -z "$1" ]]; then
	echo "Missing Payload URL" 1>&2
	exit 1
fi

if [[ -z "$2" ]]; then
	echo "Missing output filename " 1>&2
	exit 1
fi


mkdir -p dist/payload
curl -L $1 -o dist/payload/$2