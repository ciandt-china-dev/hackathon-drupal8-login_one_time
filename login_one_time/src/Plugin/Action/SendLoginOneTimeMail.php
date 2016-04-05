<?php

/**
 * @file
 * Contains action send login mail.
 */

namespace Drupal\login_one_time\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\login_one_time\LoginOneTimeSendMail;

/**
 * Send one time url.
 *
 * @Action(
 *   id = "user_send_mail_action",
 *   label = @Translation("Make selected user to send login one time mail"),
 *   type = "user"
 * )
 */
class SendLoginOneTimeMail extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    if ($account !== FALSE && $account->isActive()) {
      $server_mail = new LoginOneTimeSendMail();
      $server_mail->sendMail($account, '');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->hasPermission('send link to login one time');
  }

}
