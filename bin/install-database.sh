#!/usr/bin/env bash

npm run wp-env run tests-cli option add freshinstall yes
npm run wp-env run tests-cli user create editor editor@test.com -- --role=editor --user_pass=password --quiet
npm run wp-env run tests-cli rewrite structure '%postname%'