<?php
/**
 * @file
 * Contains \Drupal\login_one_time\Controller\LoginOneTimeController.
 */

namespace Drupal\login_one_time\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\login_one_time\Event\LoginOneTimeUsedEvent;
/**
 * Deal with one time login.
 */
class LoginOneTimeController extends ControllerBase {

  /**
   * Deal with one time login.
   */
  public function page($uid, $timestamp, $hashed_pass) {

    $user = \Drupal::currentUser();
    // Check if the user is already logged in. The back button is often the culprit here.
    if ($user->isAuthenticated()) {
      drupal_set_message(t('It is not necessary to use this link to login anymore. You are already logged in.'));

      $action = $this->getActionPath();

      if (!empty($action)) {
        return new RedirectResponse($action);
      }
      else {
        return $this->redirect("<front>");
      }
    }
    else {
      // Time out, in seconds, until login URL expires. 24 hours = 86400 seconds.
      $timeout = \Drupal::config('login_one_time.settings')->get('expiry');
      if (!$timeout) {
        $timeout = 86400 * 14;
      }
      $current = REQUEST_TIME;
      // Some redundant checks for extra security ?
      $account = User::load($uid);

      if ($account && $timestamp < $current && isset($account) && $account->isActive() == TRUE) {
        // Deny one-time login to blocked accounts.
        if (\Drupal::moduleHandler()->moduleExists('ban') && \Drupal::service('ban.ip_manager')->isBanned(\Drupal::request()->getClientIp())) {
          drupal_set_message(t('You have tried to use a one-time login for an account which has been blocked.'), 'error');
          return $this->redirect('<front>');
        }

        // Deny one-time login to accounts without permission.
        if (!$account->hasPermission('use link to login one time')) {
          drupal_set_message(t('You have tried to use a one-time login for an account which is no longer permitted to use one-time login links.'), 'error');
          return $this->redirect("<front>");
        }

        // No time out for first time login.
        if ($timeout && $account->getLastLoginTime() && $current - $timestamp > $timeout) {
          drupal_set_message(t('You have tried to use a one-time login link that has expired. Please use the log in form to supply your username and password.'));
          return $this->redirect('user.login');
        }

        elseif ($timestamp > $account->getLastLoginTime() && $timestamp < $current && $hashed_pass == user_pass_rehash($account, $timestamp)) {

          $action = $this->getActionPath();

          \Drupal::logger('user')->notice('User %name used one-time login link at time %timestamp.', array('%name' => $account->name, '%timestamp' => $timestamp));
          // Set the new user.
          $user = $account;
          // user_authenticate_finalize() also updates the login timestamp of the
          // user, which invalidates further use of the one-time login link.
          user_login_finalize($account);

          // Integrate with the rules module, see login_one_time.rules.inc.
          if (\Drupal::moduleHandler()->moduleExists('rules')) {
            //rules_invoke_event('login_one_time_send_one_time_email', $user);
            $event = new LoginOneTimeUsedEvent($account);
            $event_dispatcher = \Drupal::service('event_dispatcher');
            $event_dispatcher->dispatch(LoginOneTimeUsedEvent::EVENT_NAME, $event);
          }

          \Drupal::moduleHandler()->invokeAll('login_one_time_used', [$user]);

          // Add a session variable indicating whether the ignore current password field setting is enabled.
          $_SESSION['ignore_current_pass'] = \Drupal::config('login_one_time.settings')->get('ignore_current_pass');
          drupal_set_message(t('You have just used your one-time login link.'));
          if (!empty($action)) {
            return new RedirectResponse($action);
          }
          else {
            return $this->redirect("<front>");
          }

        }
        else {
          drupal_set_message(t('You have tried to use a one-time login link which has been used. Please use the log in form to supply your username and password.'));
          return $this->redirect('user.login');
        }
      }
      else {
        // Deny access, no more clues.
        // Everything will be in the watchdog's URL for the administrator to check.
        throw new AccessDeniedHttpException();
      }
    }
  }

  /**
   * Get the action path for redirect.
   */
  protected function getActionPath() {
    return urlencode($_REQUEST['destination']);
  }

}
