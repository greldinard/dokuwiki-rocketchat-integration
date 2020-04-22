<?php
/**
 * DokuWiki Plugin Webex Teams Notifier (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

if (!defined('DOKU_INC')) die();

require_once (DOKU_INC.'inc/changelog.php');

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class action_plugin_webexteamsnotifier extends DokuWiki_Action_Plugin {

  function register(Doku_Event_Handler $controller) {
    $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');
  }

  function handle_action_act_preprocess(Doku_Event $event, $param) {
    if ($event->data == 'save') {
      $this->handle();
    }
    return;
  }

  private function handle() {

    global $conf;
    global $ID;
    global $INFO;
    global $SUM;

    // if there is at least one namespace specified in the configuration section, only take action if the triggered
    // namespace is in the list
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
    $title    = "{$fullname} updated page [{$INFO['id']}]({$this->urlize()})";

    // compare changes
    $changelog = new PageChangeLog($ID);
    $revArr = $changelog->getRevisions(-1, 1);
    if (count($revArr) == 1) {
      $title .= " ([Compare changes]({$this->urlize($revArr[0])}))";
    }

    // markdown
    $data = array(
      "markdown"                  =>  $title
    );

    if (!empty($SUM)) {
      $data = array("markdown" => "{$title}\n\n{$SUM}");
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

    // Header Content-Type: application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    // submit payload
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{$json}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);

    // ideally display only for Admin users and/or in debugging mode
    if ($result === false){
      echo 'cURL error when posting Wiki save notification to Webex Teams: ' . curl_error($ch);
    }

    // close curl
    curl_close($ch);

  }

  private function urlize($diffRev=null) {

    global $conf;
    global $INFO;

    // Evaluating the userrewrite and useslash configuration to create proper URL,
    // for details see: https://www.dokuwiki.org/config:userewrite and https://www.dokuwiki.org/config:useslash
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

