<?php

/**
 * @file
 * Contains \Drupal\login_one_time\Plugin\Block\LoginOneTimeBlock.
 */

namespace Drupal\login_one_time\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\login_one_time\LoginOneTimeOption;

/**
 * Provides a 'LoginOneTimeBlock' block.
 *
 * @Block(
 *  id = "login_one_time_block",
 *  admin_label = @Translation("Login one time block"),
 * )
 */
class LoginOneTimeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('login_one_time.settings');
    $form['default'] = LoginOneTimeOption::selectWidget(
      $config->get('block_default'),
      t("Default path")
    );
    $form['default']['#required'] = FALSE;
    $form['default']['#description'] = t("This is where the user will be directed to upon using a <em>login one time</em> link, or the default choice when using the <em>path selection</em> widget.");
    $form['select'] = array(
      '#type' => 'checkbox',
      '#title' => t("Show <em>path selection</em> widget."),
      '#default_value' => $config->get('block_select'),
    );
    $form['set_mail'] = array(
      '#type' => 'checkbox',
      '#title' => t("Show <em>email override</em> widget."),
      '#default_value' => $config->get('block_set_mail'),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('login_one_time.settings');
    $config->set('block_default', $form_state->getValue('default'))
      ->set('block_select', $form_state->getValue('select'))
      ->set('block_set_mail', $form_state->getValue('set_mail'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('login_one_time.settings');
    $path = $config->get('block_default');
    $select = $config->get('block_select');
    $set_mail = $config->get('block_set_mail');

    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\login_one_time\Form\LoginOneTimeButtonForm', NULL, $path, $select, $set_mail);
    $content = \Drupal::service("renderer")->render($form);

    return array(
      '#markup' => $content,
    );
  }

}
