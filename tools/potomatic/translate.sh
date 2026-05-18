#!/usr/bin/env bash

POTOMATIC_OPENAI_API_KEY=$1

# Add new variables / override existing if .env file exists
if [ -f ".env" ]; then
    set -a
    source .env
    set +a
fi

if [ "$POTOMATIC_OPENAI_API_KEY" == "" ]; then
	echo "usage: $0 <openai-api-key>"
	exit 1
fi

# Rebuild Pot File
yarn i10n:make-pot

# AI Translation
yarn i10n:translate -- --abort-on-failure --api-key=$POTOMATIC_OPENAI_API_KEY
if [ "$?" != 0 ]; then
   exit 1
fi

# Build MO/PHP/JSON
yarn i10n:make-mo
yarn i10n:make-json
yarn i10n:make-php
