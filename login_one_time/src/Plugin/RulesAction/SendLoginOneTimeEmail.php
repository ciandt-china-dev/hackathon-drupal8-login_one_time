<?php
/**
 * @file
 * Contains login_one_time
 */

namespace Drupal\login_one_time\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\user\UserInterface;
use Drupal\login_one_time\LoginOneTimeSendMail;

/**
 * Provides a 'Send login one time' action.
 *
 * @RulesAction(
 *   id = "login_one_time_send_one_time_email",
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
class SendOneTimeEmail extends RulesActionBase {

  /**
   * Send account email.
   *
   * @param \Drupal\user\UserInterface $user
   *   User who should receive the notification.
   * @param string $email_type
   *   Type of email to be sent.
   */
  protected function doExecute(UserInterface $user, $path) {
    $server_mail = new LoginOneTimeSendMail();
    $server_mail->sendMail($user, $path);
  }
}
