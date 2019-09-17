# DokuWiki Rocket.Chat+ Notifier

**NOTE: This repo is unmaintained, as we no longer use Dokuwiki**

A DokuWiki plugin that notifies a Rocket.Chat channel room of wiki edits.

[Link to project page on dokuwiki.org](https://www.dokuwiki.org/plugin:rocketchatnotifier)

## Slack support
A user has reported the plugin to also work with Slack, where the plugin comes from originally. It should be a drop in replacement, but if you try this, please report back with the results.

## Dependency

This tool depends on php-curl (and by that also curl).

## Install

Clone the repository into your DokuWiku plugins folder:

```
$ git clone https://github.com/Fab-IT-ApS/dokuwiki-rocketchat-integration.git /var/lib/dokuwiki/lib/plugins/rocketchatnotifier
```

## Configure

1. Create an Incoming Webhook on Rocket.Chat

2. Enter the webhook into the rocketchat configuration section in DokuWiki's Configuration Settings

## Example

![Example notification](https://github.com/Fab-IT-ApS/dokuwiki-rocketchat-integration/raw/master/example.png)
