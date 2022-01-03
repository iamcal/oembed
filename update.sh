#!/bin/bash
git pull --rebase
aws cloudfront create-invalidation --distribution-id E1BNA5UPCVKJLN --paths "/*"  --profile oembed-cache-invalidationaws cloudfront create-invalidation --distribution-id E1BNA5UPCVKJLN --paths "/*"  --profile oembed-cache-invalidation
