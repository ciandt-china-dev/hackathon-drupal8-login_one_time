<?php

namespace Drupal\login_one_time;

use \Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Request;
use \Drupal\Core\Url;

class LoginOneTimeSendMail {
  public function sendMail(\Drupal\user\UserInterface $account, $path, $sendmail =null) {
    // test.
    //$account = user_load(1);
    $result = self::loginOneTimeSendMail($account, $path, $sendmail);

    return array("#markup" => $result);
  }

  public function loginOneTimeSendMail(\Drupal\user\UserInterface $account, $path, $sendmail) {
    $user = \Drupal::currentUser();

    if ($user->hasPermission('use link to login one time')) {
      return self::loginOneTimeMailNotify('login_one_time_key', $account, $path, $sendmail);

    }
    else {
      drupal_set_message(
        t(
          '@username is not permitted to use login one time links.  Mail not sent to this user.',
          array('@username' => $account->name)
        ),
        'warning'
      );
    }
  }

  public function loginOneTimeMailNotify($op, \Drupal\user\UserInterface $account, $path, $email = NULL, $language = NULL) {
    $params['account'] = $account;
    $params['path'] = $path;
    $email = $email ? $email : $account->getEmail();
    $language = $language ? $language : \Drupal::languageManager()->getCurrentLanguage(); //check
    // $mail = drupal_mail('login_one_time', $op, $email, $language, $params);
    $message = \Drupal::service('plugin.manager.mail')->mail('login_one_time', $op, $email, $language, $params, true);

    if ($message['send']) {
      return $email;
    }
    else {
      return FALSE;
    }
  }

  //remove
  public function loginOneTimeMailTokens($account, $language, $path = NULL){
    global $base_url;
    // @FIXME
    // url() expects a route name or an external URI.
    $tokens = array(
      '!username' => $account->name,
      '!site' => variable_get('site_name', 'Drupal'),
      //'!login_url' => login_one_time_get_link($account, $path), //
      '!login_url' => LoginOneTimeController::loginOneTimeGetLink($account, $path),
      '!uri' => $base_url,
      '!uri_brief' => preg_replace('!^https?://!', '', $base_url),
      '!mailto' => $account->mail,
      '!date' => \Drupal\Core\Datetime\DateFormatter::format(REQUEST_TIME, 'medium', '', NULL, $language->language),   ///format_date(REQUEST_TIME, 'medium', '', NULL, $language->language),
      '!login_uri' => \Drupal\Core\Url::fromRoute('user.page'),
      //'!edit_uri' => url('user/' . $account->uid . '/edit', array('absolute' => TRUE, 'language' => $language)),
      '!edit_uri' => Url::fromUri('user/' . $account->uid . '/edit', array('absolute' => TRUE, 'language' => $language)),
    );

    if (!empty($account->password)) {
      $tokens['!password'] = $account->password;
    }
    return $tokens;
  }

  public function loginOneTimeGetLink($account, $path) {
    // Path juggle - watch closely now....

    // If there is no path get the default path.
    if (!$path) {
      $path = \Drupal::config('login_one_time.settings')->get('login_one_time_path_default');
    }
    // If there is STILL no path or the path is 'current', use the current path.
    if (!$path || $path == "login_one_time[current]") {
      $path = drupal_get_path_alias($_GET['q']);
    }
    // If the path is 'front' then set it to no path.
    elseif ($path == "login_one_time[front]") {
      $path = "";
    }
    elseif ($path == "login_one_time[user_edit]") {
      $path = "user/" . $account->uid . "/edit";
    }

    $timestamp = REQUEST_TIME;

    return Url::fromUri(
      "login_one_time/" . $account->uid . "/" . $timestamp . "/" .
      user_pass_rehash($account, $timestamp), //check
      array(
        'query' => array('destination' => $path),
        'absolute' => TRUE,
        'language' => \Drupal::languageManager()->getCurrentLanguage()->getName(), //user_preferred_language($account),
      )
    );
  }

}