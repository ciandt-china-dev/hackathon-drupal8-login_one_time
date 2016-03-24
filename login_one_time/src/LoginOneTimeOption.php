<?php
/**
 * @file \Drupal\login_one_time\LoginOneTime
 */

namespace Drupal\login_one_time;

use \Drupal\Component\Utility\SafeMarkup;

class LoginOneTimeOption {

  /**
   * Generate the select widget options.
   */
  public static function selectWidget($path = NULL, $title = NULL) {
    // Set a default path if $path not given.
    if (is_null($path)) {
      $path = \Drupal::config('login_one_time.settings')->get('login_one_time_path_default');
      if (is_null($path)) {
        $path = 'login_one_time[current]';
      }
    }
    $form = array(
      '#type' => 'select',
      '#default_value' => $path,
      '#options' => array('' => t("- Choose a page -")) + self::pathOptions($path),
      '#required' => TRUE,
    );
    if ($title) {
      $form['#title'] = $title;
    }
    return $form;
  }
  
  /**
   * Generate the users widget options.
   */
  public static function userWidget($username = NULL, $title = NULL) {
    $accounts = array();
    $config = \Drupal::config('login_one_time.settings');
    if ($config->get('login_one_time_user_widget', 'autocomplete') == 'autocomplete') {
      // Only return users with a permitted role id.
      $permitted_role_ids = array_keys(user_roles(TRUE, 'use link to login one time'));
      $form = array(
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        //'#default_value' => $username,
        '#selection_settings' => [
          'filter' => ['role' => $permitted_role_ids],
        ],
        '#size' => 30,
        '#required' => TRUE,
      );
      kint($form);
    }
    else {
     $form = array(
       '#type' => 'select',
       '#default_value' => $username,
       '#options' => array('' => t("- Choose a user -")) + self::userOptions(),
       '#required' => TRUE,
     );
   }
   if ($title) {
     $form['#title'] = $title;
   }
  return $form;
}
  
  /**
   * Build the list of path select widget options.
   */
  public static function pathOptions($path = NULL) {
    $options = array();
    $config = \Drupal::config('login_one_time.settings');
    // Get variables and assemble the array.
    if ($config->get('login_one_time_path_front')
    ) {
      $options['login_one_time[front]'] = t("Front page");
    }
    if ($config->get('login_one_time_path_user')
    ) {
      $options['user'] = t("User page");
    }
    if ($config->get('login_one_time_path_user_edit')
    ) {
      $options['login_one_time[user_edit]'] = t("User edit page");
    }
    if ($config->get('login_one_time_path_current')
    ) {
      $options['login_one_time[current]'] = t("Current page");
    }
    if ($config->get('login_one_time_path_custom')
    ) {
      $customs = explode("\n", $config->get('login_one_time_path_custom'));
      if (is_array($customs)) {
        foreach ($customs as $custom) {
          $custom_option = explode("|", $custom);
          $options[$custom_option[0]] = $custom_option[1] ? $custom_option[1] : $custom_option[0];
        }
      }
    }

    // Include the $path in the $options, if not already there.
    // This may override some settings in some cases, but it kinda means those
    // settings were incomplete.
    if ($path && !isset($options[$path])) {
      if ($path == "login_one_time[current]") {
        $display = t("Current page");
      }
      elseif ("login_one_time[front]") {
        $display = t("Front page");
      }
      else {
        $display = $path;
      }
      $options[$path] = $display;
    }

    // Allow modules to modify this list correctly.
    \Drupal::moduleHandler()->alter("login_one_time_path_options", $options);

    return $options;
  }

  /**
   * Build the list of user select widget options.
   */
  public static function userOptions($autocomplete = NULL) {
    $options = array();

    // Only return users with a permitted role id.
    $permitted_role_ids = array_keys(user_roles(TRUE, 'use link to login one time'));
    if (!empty($permitted_role_ids)) {
      $args = array();
      $args[':rids[]'] = $permitted_role_ids;
      $where = '';
      if ($autocomplete) {
        $where = " AND u.name LIKE :autocomplete";
        $args[':autocomplete'] = '%' . $autocomplete . '%';
      }
      $result = db_query(
        'SELECT u.name AS name FROM {users_field_data} u'
        . ' INNER JOIN {user__roles} ur ON u.uid = ur.entity_id AND ur.roles_target_id IN ( :rids[] )'
        . ' WHERE u.status <> 0'
        . $where
        . ' ORDER BY u.name',
        $args
      )->fetchAll();
      foreach ($result as $row) {
        $options[$row->name] = SafeMarkup::checkPlain($row->name);
      }
    }

    // Allow modules to modify this list correctly.
    \Drupal::moduleHandler()->alter("login_one_time_user_options", $options);

    return $options;
  }
}
