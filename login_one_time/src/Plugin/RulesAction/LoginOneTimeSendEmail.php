<?php
/**
 * @file
 * Contains login_one_time
 */

namespace Drupal\login_one_time\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\login_one_time\LoginOneTimeSendMail;

/**
 * Provides a 'Send login one time' action.
 *
 * @RulesAction(
 *   id = "rules_login_one_time_send_email",
 *   label = @Translation("Send a login one-time email."),
 *   category = @Translation("User"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       description = @Translation("The user to whom we send one time e-mail.")
 *     ),
 *     "path" = @ContextDefinition("string",
 *       label = @Translation("Destination path"),
 *       description = @Translation("The Destination path."),
 *     )
 *   }
 * )
 */
class LoginOneTimeSendEmail extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->hasPermission('send link to login one time');
  }

  /**
   * Send account email.
   *
   * @param \Drupal\user\UserInterface $user
   *   User who should receive the notification.
   * @param string $email_type
   *   Type of email to be sent.
   */
  protected function doExecute(AccountInterface $user, $path) {
    if ($user !== FALSE && $user->isActive()) {
      $server_mail = new LoginOneTimeSendMail();
      $server_mail->sendMail($user, $path);
    }
  }
}
