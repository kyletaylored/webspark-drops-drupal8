<?php

// Important constants :)
$pantheon_yellow = '#EFD01B';

// Default values for parameters - this will assume the channel you define the webhook for.
// The full Slack Message API allows you to specify other channels and enhance the messagge further
// if you like: https://api.slack.com/docs/messages/builder
$defaults = array(
  'slack_username' => 'Pantheon',
  'always_show_text' => false,
);

// Load our hidden credentials.
$secrets = _get_secrets($defaults);

// Build an array of fields to be rendered with Slack Attachments as a table
// attachment-style formatting:
// https://api.slack.com/docs/attachments
$fields = array(
  array(
    'title' => 'Site',
    'value' => $_ENV['PANTHEON_SITE_NAME'],
    'short' => 'true'
  ),
  array( // Render Environment name with link to site, <http://{ENV}-{SITENAME}.pantheon.io|{ENV}>
    'title' => 'Environment',
    'value' => '<http://' . $_ENV['PANTHEON_ENVIRONMENT'] . '-' . $_ENV['PANTHEON_SITE_NAME'] . '.pantheonsite.io|' . $_ENV['PANTHEON_ENVIRONMENT'] . '>',
    'short' => 'true'
  ),
  array( // Render Name with link to Email from Commit message
    'title' => 'By',
    'value' => $_POST['user_email'],
    'short' => 'true'
  ),
  array( // Render workflow phase that the message was sent
    'title' => 'Workflow',
    'value' => ucfirst($_POST['stage']) . ' ' . str_replace('_', ' ',  $_POST['wf_type']),
    'short' => 'true'
  ),
  array(
    'title' => 'View Dashboard',
    'value' => '<https://dashboard.pantheon.io/sites/'. PANTHEON_SITE .'#'. PANTHEON_ENVIRONMENT .'/deploys|View Dashboard>',
    'short' => 'true'
  ),
);

// Customize the message based on the workflow type.  Note that slack_notification.php
// must appear in your pantheon.yml for each workflow type you wish to send notifications on.
switch($_POST['wf_type']) {
  case 'deploy':
  case 'deploy_product':
    // Find out what tag we are on and get the annotation.
    $deploy_tag = `git describe --tags`;
    $deploy_message = $_POST['deploy_message'];

    // Prepare the slack payload as per:
    // https://api.slack.com/incoming-webhooks
    $text = 'Deploy to the '. $_ENV['PANTHEON_ENVIRONMENT'];
    $text .= ' environment of '. $_ENV['PANTHEON_SITE_NAME'] .' by '. $_POST['user_email'] .' complete!';
    $text .= ' <https://dashboard.pantheon.io/sites/'. PANTHEON_SITE .'#'. PANTHEON_ENVIRONMENT .'/deploys|View Dashboard>';
    // Build an array of fields to be rendered with Slack Attachments as a table
    // attachment-style formatting:
    // https://api.slack.com/docs/attachments
    $fields[] = array(
      'title' => 'Details',
      'value' => $text,
      'short' => 'false'
    );
    $fields[] = array(
      'title' => 'Deploy Note',
      'value' => $deploy_message,
      'short' => 'false'
    );  
    break;

  case 'sync_code':
    // Get the committer, hash, and message for the most recent commit.
    $committer = `git log -1 --pretty=%cn`;
    $email = `git log -1 --pretty=%ce`;
    $message = `git log -1 --pretty=%B`;
    $hash = `git log -1 --pretty=%h`;

    // Prepare the slack payload as per:
    // https://api.slack.com/incoming-webhooks
    $text = 'Code sync to the ' . $_ENV['PANTHEON_ENVIRONMENT'] . ' environment of ' . $_ENV['PANTHEON_SITE_NAME'] . ' by ' . $_POST['user_email'] . "!\n";
    $text .= 'Most recent commit: ' . rtrim($hash) . ' by ' . rtrim($committer) . ': ' . $message;
    // Build an array of fields to be rendered with Slack Attachments as a table
    // attachment-style formatting:
    // https://api.slack.com/docs/attachments
    $fields += array(
      array(
        'title' => 'Commit',
        'value' => rtrim($hash),
        'short' => 'true'
      ),
      array(
        'title' => 'Commit Message',
        'value' => $message,
        'short' => 'false'
      )
    );
    break;

  case 'clear_cache':
    $fields[] = array(
      'title' => 'Cleared caches',
      'value' => 'Cleared caches on the ' . $_ENV['PANTHEON_ENVIRONMENT'] . ' environment of ' . $_ENV['PANTHEON_SITE_NAME'] . "!\n",
      'short' => 'false'
    );
    break;

  default:
    $text = $_POST['qs_description'];
    break;
}

$attachment = array(
  'fallback' => $text,
  'pretext' => ($_POST['wf_type'] == 'clear_cache') ? 'Caches cleared :construction:' : 'Deploying :rocket:',
  'color' => $pantheon_yellow, // Can either be one of 'good', 'warning', 'danger', or any hex color code
  'fields' => $fields
);

_slack_notification($secrets['url'], $text, $attachment, $secrets['always_show_text']);


/**
 * Get secrets from secrets file.
 *
 * @param array $requiredKeys  List of keys in secrets file that must exist.
 */
function _get_secrets($defaults)
{
  // $secretsContents = file_get_contents('https://us-central1-speedmob-api.cloudfunctions.net/Slack');
  // $secrets = json_decode($secretsContents, 1);
  // if ($secrets == false) {
  //   die('Could not parse json in secrets file. Aborting!');
  // }

  $secrets += $defaults;
  $secrets['url'] = 'https://hooks.slack.com/services/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';
  
  return $secrets;
}

/**
 * Send a notification to slack
 */
function _slack_notification($slack_url, $text, $attachment, $alwaysShowText = false)
{
  $attachment['fallback'] = $text;
  $post = array(
    // 'icon_emoji' => ':lightning_cloud:',
    'icon_url' => 'https://pantheon.io/sites/all/themes/zeus/images/icons/logo-pantheon--icon.svg',
    'attachments' => array($attachment)
  );
  if ($alwaysShowText) {
    $post['text'] = $text;
  }
  $payload = json_encode($post);
  $opts = [
    'http' => [
      'method'  => 'POST',
      'header'  => 'Content-type: application/json',
      'content' => $payload,
    ]
  ];
  $context  = stream_context_create($opts);
  $result = file_get_contents($slack_url, false, $context);
  var_dump($result);

  // Watch for messages with `terminus workflows watch --site=SITENAME`
  print("\n==== Posting to Slack ====\n");
  $result = curl_exec($ch);
  print("RESULT: $result");
  // $payload_pretty = json_encode($post,JSON_PRETTY_PRINT); // Uncomment to debug JSON
  // print("JSON: $payload_pretty"); // Uncomment to Debug JSON
  print("\n===== Post Complete! =====\n");
  curl_close($ch);
}
