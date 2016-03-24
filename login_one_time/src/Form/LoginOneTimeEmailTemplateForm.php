<?php
/**
 * @file \Drupal\login_one_time\LoginOneTimeEmailTemplateForm
 */

namespace Drupal\login_one_time\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LoginOneTimeEmailTemplateForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_one_time_user_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['login_one_time.user_admin_settings'];
  }

  /**
   * Create email settings as part of user admin settings
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // These email tokens are shared for all settings, so just define
    // the list once to help ensure they stay in sync.
    $email_token_help = $this->t('Available variables are: [site:name], [site:url], [user:display-name], [user:account-name], [user:mail], [site:login-url], [site:url-brief], [user:edit-url], [user:one-time-login-url], [user:cancel-url].');
    $mail_config = $this->config('login_one_time.settings');

    $form['email_login_one_time'] = array(
      '#type' => 'details',
      '#title' => $this->t('Login one time e-mail'),
      '#description' => $this->t('Customize login one time e-mail messages sent to users.') . ' ' . $email_token_help,
      '#group' => 'email',
    );
    $form['email_login_one_time']['login_one_time_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('email_login_one_time.subject'),
      '#maxlength' => 180,
    );
    $form['email_login_one_time']['login_one_time_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mail_config->get('email_login_one_time.body'),
      '#rows' => 12,
    );
    
    return $form;
  }

  /**
   * Save the email template
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    die();
    kint($form);
  }
}