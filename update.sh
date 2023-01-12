#!/bin/bash
git pull --rebase
key=$(cat secrets/cloudflare-api-token)
curl -X POST "https://api.cloudflare.com/client/v4/zones/81e818e9878c5dc03727566c901a51d7/purge_cache" \
     -H "Authorization: Bearer $key" \
     -H "Content-Type: application/json" \
     --data '{"purge_everything":true}'
