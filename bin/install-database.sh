#!/usr/bin/env bash

npm run env cli option add freshinstall yes
npm run env cli user create editor editor@test.com -- --role=editor --user_pass=password --quiet
npm run env cli rewrite structure '%postname%'