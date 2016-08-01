<?php
/**
 * DokuWiki Plugin Rocket.Chat Notifier (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

if (!defined('DOKU_INC')) die();

require_once (DOKU_INC.'inc/changelog.php');

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class action_plugin_rocketchatnotifier extends DokuWiki_Action_Plugin {

  function register(Doku_Event_Handler $controller) {
    $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');
  }

  function handle_action_act_preprocess(Doku_Event $event, $param) {
    if (isset($event->data['save'])) {
      if ($event->data['save'] == $lang['btn_save']) {
        $this->handle();
      }
    }
    return;
  }

  private function handle() {

    global $conf;
    global $ID;
    global $INFO;
    global $SUM;

    // filter by namespaces
    $ns = $this->getConf('namespaces');
    if (!empty($ns)) {
      $namespaces = explode(',', $ns);
      $current_namespace = explode(':', $INFO['namespace']);
      if (!in_array($current_namespace[0], $namespaces)) {
        return;
      }
    }

    // title
    $fullname = $INFO['userinfo']['name'];
    $page     = $INFO['namespace'] . $INFO['id'];
    $title    = "{$fullname} updated page <{$this->urlize()}|{$INFO['id']}>";

    // compare changes
    $changelog = new PageChangeLog($ID);
    $revArr = $changelog->getRevisions(-1, 1);
    if (count($revArr) == 1) {
      $title .= " (<{$this->urlize($revArr[0])}|Compare changes>)";
    }

    // text
    $data = array(
      "text"                  =>  $title
    );

    // attachments
    if (!empty($SUM)) {
      $data['attachments'] = array(array(
        "title_link"       => "{$this->urlize()}",
        "title"            => "Summary",
        "text"             => "{$SUM}\n- {$fullname}",
        "color"            => "#AB4531"
      ));
    }

    // encode data
    $json = json_encode($data);

    // init curl
    $webhook = $this->getConf('webhook');
    $ch = curl_init($webhook);

    // use proxy if defined
    $proxy = $conf['proxy'];
    if (!empty($proxy['host'])) {

      // configure proxy address and port
      $proxyAddress = $proxy['host'] . ':' . $proxy['port'];

      curl_setopt($ch, CURLOPT_PROXY, $proxyAddress);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      // TODO: may be required internally but best to add a config flag/path to local certificate file
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

      // include username and password if defined
      if (!empty($proxy['user']) && !empty($proxy['pass'])) {
        $proxyAuth = $proxy['user'] . ':' . conf_decodeString($proxy['port']);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
      }

    }

    // submit payload
    $pay = urlencode($json);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "payload={$pay}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);

    // ideally display only for Admin users and/or in debugging mode
    if ($result === false){
      echo 'cURL error when posting Wiki save notification to Rocket.Chat+: ' . curl_error($ch);
    }

    // close curl
    curl_close($ch);

  }

  private function urlize($diffRev=null) {

    global $conf;
    global $INFO;

    switch($conf['userewrite']) {
    case 0:
      if (!empty($diffRev)) {
        $url = DOKU_URL . "doku.php?id={$INFO['id']}&rev={$diffRev}&do=diff";
      } else {
        $url = DOKU_URL . "doku.php?id={$INFO['id']}";
      }
      break;
    case 1:
      $id = $INFO['id'];
      if ($conf['useslash']) {
        $id = str_replace(":", "/", $id);
      }
      if (!empty($diffRev)) {
        $url = DOKU_URL . "{$id}?rev={$diffRev}&do=diff";
      } else {
        $url = DOKU_URL . $id;
      }
      break;
    case 2:
      $id = $INFO['id'];
      if ($conf['useslash']) {
        $id = str_replace(":", "/", $id);
      }
      if (!empty($diffRev)) {
        $url = DOKU_URL . "doku.php/{$id}?rev={$diffRev}&do=diff";
      } else {
        $url = DOKU_URL . "doku.php/{$id}";
      }
      break;
    }
    return $url;
  }
}

// vim:ts=4:sw=4:et:enc=utf-8:
