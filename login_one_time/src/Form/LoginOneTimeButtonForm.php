<?php
/**
 * @file \Drupal\login_one_time\LoginOneTimeButtonForm
 */

namespace Drupal\login_one_time\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\login_one_time\LoginOneTimeOption;
use Drupal\login_one_time\LoginOneTimeSendMail;

class LoginOneTimeButtonForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_one_time_button';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['login_one_time.settings'];
  }

  /**
   * Form to send a one-time login link
   *
   * @param $form
   * @param $form_state
   * @param $username
   *   If supplied force the email to go to this user, if not supplied will
   *   display a select element with all active users. NOTE: It is assumed that
   *   this user has permission to use login one time links, if they do not the
   *   button will still appear but the mail will not be sent.
   * @param $path
   *   If supplied will force the emailed link to redirect to this path. If not
   *   supplied will use default setting, or fallback to the URL of the page this
   *   code is called from.  Supply empty string to prompt for selection.
   * @param $select
   *   If TRUE will display a select element to choose from configured paths, the
   *   default choice will come from $path or be calculated the same way, or if
   *   empty string supplied it will prompt for selection.
   * @param $set_mail
   *   If TRUE shows textbox to override the recipient email address.
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $username = NULL, $path = NULL, $select = FALSE, $set_mail = FALSE) {
    $form = array();
    $form['#redirect'] = FALSE;
    if ($username) {
      $form['account'] = array(
        '#type' => 'value',
        '#value' => $username,
      );
      $account = user_load_by_name($username);
      $button_text = t('Send login one time link to @username', array('@username' => user_format_name($account)));
    }
    else {
      $form['account'] = LoginOneTimeOption::userWidget($username);
      $button_text = t('Send login one time link');
    }
    if ($select) {
      $form['path'] = LoginOneTimeOption::selectWidget($path);
    }
    else {
      $form['path'] = array(
        '#type' => 'value',
        '#value' => $path,
      );
    }
    if ($set_mail) {
      $form['set_mail'] = array(
        '#type' => 'textfield',
        '#title' => t('Email override'),
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $button_text,
    );
    //if (isset($form_state['storage']['done']) && $form_state['storage']['done']) {
    /*if ($form_state->getValue()['done']) {
      $form['submit']['#disabled'] = TRUE;
    }*/

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send login one time link'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * Validate function for the form to send a one-time login link.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('set_mail'))) {
      if (!\Drupal::service('email.validator')
        ->isValid($form_state->getValue('set_mail'))
      ) {
        $form_state->setErrorByName('set_mail', t('Invalid email address.'));
      }
    }
  }

  /**
   * Submit function for the form to send a one-time login link.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $accounts = \Drupal::entityManager()
      ->getStorage('user')
      ->loadByProperties(array('name' => $form_state->getValue('account')));
    $account = reset($accounts);
    $set_mail = !empty($form_state->getValue('set_mail')) ? $form_state->getValue('set_mail') : NULL;

    $sendMailService =  new LoginOneTimeSendMail();
    $result = $sendMailService->sendMail($account, $form_state->getValue('path'), $set_mail);
    if ($result) {
      drupal_set_message(
        t(
          "A one-time login link has been sent to @username.",
          array('@username' => $account->getAccountName())
        )
      );
    }
    else {
      drupal_set_message(
        t("There was a problem sending the one-time login link."),
        'error'
      );
    }
  }

}