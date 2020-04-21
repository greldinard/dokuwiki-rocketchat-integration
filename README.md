# DokuWiki Webex Teams Notifier

A DokuWiki plugin that notifies a Webex Teams Webhook of wiki edits.

## Dependency

This tool depends on php-curl (and by that also curl).

## Install

Clone the repository into your DokuWiku plugins folder:

```
$ git clone https://github.com/greldinard/dokuwiki-rocketchat-integration.git /var/lib/dokuwiki/lib/plugins/webexteamsnotifier
```

## Configure

1. Create an Incoming Webhook on Webex Teams (https://apphub.webex.com/teams/applications/incoming-webhooks-cisco-systems)

2. Enter the webhook into the Webex Teams configuration section in DokuWiki's Configuration Settings
