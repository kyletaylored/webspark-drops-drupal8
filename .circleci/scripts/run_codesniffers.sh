#!/bin/bash

# Setup required variables
PR_NUMBER=${CI_PULL_REQUEST##*/}
PR_API_URL="https://api.github.com/repos/${CIRCLE_PROJECT_USERNAME}/${CIRCLE_PROJECT_REPONAME}/pulls/${PR_NUMBER}"
BASE_BRANCH=$(curl -s -H "Authorization: token ${GITHUB_TOKEN}" $PR_API_URL | jq -r '.base.sha')
PR_BRANCH=$(curl -s -H "Authorization: token ${GITHUB_TOKEN}" $PR_API_URL | jq -r '.head.sha')
CHANGED_FILES=$(git diff --name-only ${BASE_BRANCH}..${PR_BRANCH} | tr '\r\n' ' ')
TMP_REPORT=/tmp/codeclimate_report.html

# Run Codeclimate on changed files.
echo "Running Codeclimate code sniffers..."

docker run \
  --interactive --tty --rm \
  --env CODECLIMATE_CODE="$PWD" \
  --volume "$PWD":/code \
  --volume /var/run/docker.sock:/var/run/docker.sock \
  --volume /tmp/cc:/tmp/cc \
  codeclimate/codeclimate analyze -f html ${CHANGED_FILES} > ${TMP_REPORT}
