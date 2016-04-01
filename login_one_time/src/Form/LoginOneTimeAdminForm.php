<?php

/**
 * @file
 * Drupal\login_one_time\LoginOneTimeAdminForm.
 */

namespace Drupal\login_one_time\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\login_one_time\LoginOneTimeOption;

/**
 * Class LoginOneTimeAdminForm.
 *
 * @package Drupal\login_one_time\Form
 */
class LoginOneTimeAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_one_time_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['login_one_time.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('login_one_time.settings');
    $form['global'] = array(
      '#type'  => 'fieldset',
      '#title' => t("Global settings"),
    );
    $form['global']['login_one_time_expiry'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Link expiry'),
      '#default_value' => $config->get('expiry'),
      '#size'          => strlen(PHP_INT_MAX),
      '#maxlength'     => strlen(PHP_INT_MAX),
      '#description'   => t("How long, in seconds, before links expire.  Leave blank to default to two weeks (1,209,600 seconds)."),
    );
    $form['global']['login_one_time_user_widget'] = array(
      '#type'          => 'radios',
      '#title'         => t('User selection widget'),
      '#default_value' => $config->get('user_widget'),
      '#options'       => array(
        'autocomplete' => t('Autocomplete textfield'),
        'select'       => t('Select list'),
      ),
      '#description'   => t("If using a <em>user selection</em> widget, this configures the form element type.  For smaller sites a select list could be easier to use."),
    );

    $form['mail'] = array(
      '#type'  => 'fieldset',
      '#title' => t("E-mail settings"),
    );
    $form['mail']['login_one_time_mail_message'] = array(
      '#markup' => t('Customize <em>login one time</em> e-mail messages sent to users at the <a href="!user_settings">User settings</a> page.', array(
        '!user_settings' => Url::fromRoute('entity.user.admin_form')
          ->toString(),
      )),
    );

    $form['path'] = array(
      '#type'  => 'fieldset',
      '#title' => t("Path settings"),
    );
    $form['path']['listed'] = array(
      '#type'        => 'fieldset',
      '#title'       => t("Listed"),
      '#description' => t("Which paths to make available for selection."),
    );
    $form['path']['listed']['login_one_time_path_front'] = array(
      '#type'          => 'checkbox',
      '#title'         => t("Front page"),
      '#description'   => t("The front page of the website."),
      '#default_value' => $config->get('path_front'),
    );
    $form['path']['listed']['login_one_time_path_user'] = array(
      '#type'          => 'checkbox',
      '#title'         => t("User page"),
      '#description'   => t("The user's account page."),
      '#default_value' => $config->get('path_user'),
    );
    $form['path']['listed']['login_one_time_path_user_edit'] = array(
      '#type'          => 'checkbox',
      '#title'         => t("User edit page"),
      '#description'   => t("The user's account edit page."),
      '#default_value' => $config->get('path_user_edit'),
    );
    $form['path']['listed']['login_one_time_path_current'] = array(
      '#type'          => 'checkbox',
      '#title'         => t("Current page"),
      '#description'   => t("Page from which <em>login one time</em> e-mail is sent."),
      '#default_value' => $config->get('path_current'),
    );
    $form['path']['listed']['login_one_time_path_custom'] = array(
      '#type'          => 'textarea',
      '#title'         => t('Custom paths'),
      '#default_value' => $config->get('path_custom'),
      '#description'   => t("Enter one path per line.  You may also supply a display name for the path using a key|value pair, where the key is the path and the value is the display name."),
    );
    $form['path']['default'] = array(
      '#type'        => 'fieldset',
      '#title'       => t("Default path"),
      '#description' => t("This is where the user will be directed to upon using a <em>login one time</em> link, or the default choice when using the <em>path selection</em> widget."),
    );
    $form['path']['default']['login_one_time_path_default'] = LoginOneTimeOption::selectWidget($config->get('path_default'), t("Default path"));
    $form['path']['default']['login_one_time_path_default']['#required'] = FALSE;

    $form['user'] = array(
      '#type'  => 'fieldset',
      '#title' => t("User account page settings"),
    );
    $form['user']['login_one_time_user_view'] = array(
      '#type'          => 'checkbox',
      '#title'         => t("Show <em>login one time</em> button."),
      '#description'   => t("Permitted users will be able to e-mail the link via a button on the user's account page."),
      '#default_value' => $config->get('user_view'),
    );
    $form['user']['login_one_time_user_select'] = array(
      '#type'          => 'checkbox',
      '#title'         => t("Show <em>path selection</em> widget."),
      '#description'   => t("Will only show when the button is shown as well."),
      '#default_value' => $config->get('user_select'),
    );
    $form['user']['login_one_time_user_set_mail'] = array(
      '#type'          => 'checkbox',
      '#title'         => t("Show <em>email override</em> widget."),
      '#description'   => t("To send to an email address other than the account email.  Will only show when the button is shown as well."),
      '#default_value' => $config->get('user_set_mail'),
    );
    $form['user']['login_one_time_user_ignore_current_pass'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Ignore the <em>current password</em> field'),
      '#description'   => t('Will ignore and hide the current password field when on the user edit page.'),
      '#default_value' => $config->get('user_ignore_current_pass'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Expiry time must be either empty or a number.
    $time_expiry = $form_state->getValue('login_one_time_expiry');
    if (!empty($time_expiry) && !is_numeric($time_expiry)) {
      $form_state->setErrorByName('login_one_time_expiry', t('The link expiry time must be a number.'));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('login_one_time.settings');
    // Set default expiry time if user doesn't provide.
    $expiry_time = ltrim($form_state->getValue('login_one_time_expiry'));
    if (empty($expiry_time)) {
      $expiry_time = '1209600';
    }
    $config->set('expiry', $expiry_time)
      ->set('user_widget', $form_state->getValue('login_one_time_user_widget'))
      ->set('mail_message', $form_state->getValue('login_one_time_mail_message'))
      ->set('path_front', $form_state->getValue('login_one_time_path_front'))
      ->set('path_user', $form_state->getValue('login_one_time_path_user'))
      ->set('path_user_edit', $form_state->getValue('login_one_time_path_user_edit'))
      ->set('path_current', $form_state->getValue('login_one_time_path_current'))
      ->set('path_custom', $form_state->getValue('login_one_time_path_custom'))
      ->set('path_default', $form_state->getValue('login_one_time_path_default'))
      ->set('user_view', $form_state->getValue('login_one_time_user_view'))
      ->set('user_select', $form_state->getValue('login_one_time_user_select'))
      ->set('user_set_mail', $form_state->getValue('login_one_time_user_set_mail'))
      ->set('user_ignore_current_pass', $form_state->getValue('login_one_time_user_ignore_current_pass'))
      ->save();
  }

}
