<?php

/**
 * Event that is fired when the user has used their one time login.
 *
 * @see login_one_time_used()
 */

namespace Drupal\login_one_time\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\user\UserInterface;

class LoginOneTimeUsedEvent extends Event {

  const EVENT_NAME = 'rules_login_one_time_used';

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account of the user logged in.
   */
  public function __construct(UserInterface $account) {
    $this->account = $account;
  }

}