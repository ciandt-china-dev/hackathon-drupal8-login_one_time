<?php

/**
 * @file
 * Login One Time Send Mail.
 */

namespace Drupal\login_one_time;

/**
 * @file
 * Contains \Drupal\login_one_time\LoginOneTimeSendMail.
 */

use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Login One Time Module Sent mail.
 */
class LoginOneTimeSendMail {

  /**
   * Return Send mail result.
   *
   * @param \Drupal\user\UserInterface $account
   *   Account.
   * @param string $path
   *   Destination path.
   * @param string $sendmail
   *   Send mail.
   *
   * @return array
   *   Drupal markup.
   */
  public function sendMail(UserInterface $account, $path, $sendmail = NULL) {
    $result = $this->loginOneTimeSendMail($account, $path, $sendmail);
    return array("#markup" => $result);
  }

  /**
   * Send mail.
   *
   * @param UserInterface $account
   *   Account.
   * @param string $path
   *   Destination path.
   * @param string $sendmail
   *   Send mail.
   *
   * @return bool|null|string
   */
  public function loginOneTimeSendMail(UserInterface $account, $path, $sendmail) {
    $user = \Drupal::currentUser();
    if ($user->hasPermission('use link to login one time')) {
      return $this->loginOneTimeMailNotify('login_one_time_key', $account, $path, $sendmail);

    }
    else {
      drupal_set_message(t('@username is not permitted to use login one time links.  Mail not sent to this user.', array('@username' => $account->getAccountName())), 'warning');
    }
  }

  /**
   * Login one time mail notify.
   *
   * @param string $op
   *   This is the key.
   * @param UserInterface $account
   *   Account.
   * @param string $path
   *   Destination path.
   * @param string $email
   *   Send to mail.
   * @param string $language
   *   Language object.
   *
   * @return bool|null|string
   *   Notify success or not.
   */
  public function loginOneTimeMailNotify($op, UserInterface $account, $path, $email = NULL, $language = NULL) {
    $params['account'] = $account;
    $language = $account->language();
    $params['path']    = $path;
    $email             = $email ? $email : $account->getEmail();
    $language          = $language ? $language : \Drupal::languageManager()
      ->getCurrentLanguage();
    $params['language'] = $language;
    $message           = \Drupal::service('plugin.manager.mail')
      ->mail('login_one_time', $op, $email, $language, $params, TRUE);

    if ($message['send']) {
      return $email;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Return an array of token to value mappings for user e-mail messages.
   *
   * @param object $account
   *   The user object of the account being notified.  Must contain at
   *   least the fields 'uid', 'name', and 'mail'.
   * @param string $language
   *   Language object to generate the tokens with.
   *
   * @return array
   *   Array of mappings from token names to values (for use with strtr()).
   *
   * @todo: To be deprecated.
   */
  public function loginOneTimeMailTokens($account, $language, $path = NULL) {
    global $base_url;
    $config = \Drupal::config('system.site');
    $tokens = array(
      '!username' => $account->getAccountName(),
      '!site' => $config->get('name') ? $config->get('name') : 'drupal',
      '!login_url' => $this->loginOneTimeGetLink($account, $path),
      '!uri' => $base_url,
      '!uri_brief' => preg_replace('!^https?://!', '', $base_url),
      '!mailto' => $account->getEmail(),
      '!date' => format_date(REQUEST_TIME),
      '!login_uri' => Url::fromUserInput('/user', array('absolute' => TRUE, 'language' => $language))->toString(),
      '!edit_uri' => Url::fromUserInput('/user/' . $account->get('uid')->value . '/edit', array('absolute' => TRUE, 'language' => $language))->toString(),
    );

    if (!empty($account->password)) {
      $tokens['!password'] = $account->password;
    }
    return $tokens;
  }

  /**
   * Returns a mail string for a variable name.
   *
   * Used by user_mail() and the settings forms to retrieve strings.
   */
  public function loginOneTimeMailText($key, $path = NULL, $language = NULL, $variables = array()) {

    if (empty($language)) {
      $lan_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    $config = \Drupal::config('login_one_time.settings');

    if ($var = $config->get($key)) {
      // An admin setting overrides the default string.
      return $var;
    }
    else {
      $langcode = isset($lan_id) ? $lan_id : NULL;
      $options = array();
      if (!is_null($langcode)) {
        $options['langcode'] = $langcode;
      }
      // No override, return default string.
      switch ($key) {
        case 'email_template.subject':
          return t('One-time login link for [user:name] at [site:name]', $variables, $options);

        case 'email_template.body':
          return t("[user:name],\n\nA request to give you a one-time login for your account has been made at [site:name].\n\nYou may now log in to [site:url-brief] by clicking on this link or copying and pasting it in your browser:\n\n[user:login-one-time]\n\nThis is a one-time login, so it can be used only once.  It expires in two weeks and nothing will happen if it's not used.\n\n--  [site:name] team", $variables, $options);
      }
    }
  }

  /**
   * Generate a one-time link for the $account.
   */
  public function loginOneTimeGetLink($account, $path = NULL) {

    // Path juggle - watch closely now....
    // If there is no path get the default path.
    if (!$path) {
      $path = \Drupal::config('login_one_time.settings')->get('path_default');
    }

    // If there is STILL no path or the path is 'current', use the current path.
    if (!$path || $path == "login_one_time[current]") {
      $url = Url::fromRoute('<current>');
      $path = $url->getInternalPath();
    }
    // If the path is 'front' then set it to no path.
    elseif ($path == "login_one_time[front]") {
      $path = "";
    }
    elseif ($path == "login_one_time[user_edit]") {
      $path = "user/" . $account->get('uid')->value . "/edit";
    }

    $timestamp = REQUEST_TIME;
    $id = $account->get('uid')->value;
    $hash = user_pass_rehash($account, $timestamp);
    $url = Url::fromRoute(
      "login_one_time.page",
      array('uid' => $id, 'timestamp' => $timestamp, 'hashed_pass' => $hash),
      array(
        'query' => array('destination' => $path),
        'absolute' => TRUE,
        'language' => $account->language(),
      )
    )->toString();
    return $url;
  }

}
