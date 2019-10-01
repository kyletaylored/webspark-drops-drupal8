#!/bin/bash

# Setup Required Variables
PR_NUMBER=${CI_PULL_REQUEST##*/}
PR_API_URL="https://api.github.com/repos/${CIRCLE_PROJECT_USERNAME}/${CIRCLE_PROJECT_REPONAME}/pulls/${PR_NUMBER}"
ARTIFACTS_URL="https://circleci.com/api/v1.1/project/github/$CIRCLE_PROJECT_USERNAME/$CIRCLE_PROJECT_REPONAME/$CIRCLE_BUILD_NUM/artifacts?circle-token=$CIRCLECI_TOKEN"
REPORT_URL=$(curl $ARTIFACTS_URL | jq -r '.[0].url')
PR_MESSAGE_URL="https://api.github.com/repos/$CIRCLE_PROJECT_USERNAME/$CIRCLE_PROJECT_REPONAME/issues/$PR_NUMBER/comments"
PR_MESSAGE="View the code sniffer report: $REPORT_URL"

# Post Message to PR on Github.
curl -H "Authorization: token ${GITHUB_TOKEN}" --request POST --data "{\"body\": \"${PR_MESSAGE}\"}" $PR_MESSAGE_URL