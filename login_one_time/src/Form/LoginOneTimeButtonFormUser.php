<?php
/**
 * @file
 * Drupal\login_one_time\LoginOneTimeButtonFormUser.
 */

namespace Drupal\login_one_time\Form;

/**
 * Class LoginOneTimeButtonFormUser.
 *
 * @package Drupal\login_one_time\Form
 */
class LoginOneTimeButtonFormUser extends LoginOneTimeButtonForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_one_time_button_user';
  }

}
