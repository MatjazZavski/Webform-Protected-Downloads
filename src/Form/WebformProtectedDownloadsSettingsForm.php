<?php
/**
 * @file
 * Contains \Drupal\webform_protected_downloads\Form\WebformProtectedDownloadsSettingsForm.
 */
namespace Drupal\webform_protected_downloads\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Config;

class WebformProtectedDownloadsSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_protected_downloads_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get current webform ID from the URL.
    $params = \Drupal::routeMatch()->getParameters();
    $webform_id = $params->get('webform');
    $webform = Webform::load($webform_id);

    // Get form settings.
    $webform_settings = $webform->getThirdPartySettings('webform_protected_downloads');

    // Create the form.
    $form['enabled_protected_files'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable serving protected files after webform submit - <b>Must be checked for other options to work</b>'),
      '#default_value' => $webform_settings['enabled_protected_files'] ? $webform_settings['enabled_protected_files'] : FALSE,
    ];
    $form['expire_after'] = [
      '#type' => 'number',
      '#title' => t('Expire after X minutes'),
      '#default_value' => $webform_settings['expire_after'] ? $webform_settings['expire_after'] : '',
    ];
    $form['enabled_onetime'] = [
      '#type' => 'checkbox',
      '#title' => t('One time visit link'),
      '#default_value' => $webform_settings['enabled_onetime'] ? $webform_settings['enabled_onetime'] : FALSE,
    ];
    $form['protected_file'] = [
      '#name' => 'protected_file',
      '#type' => 'managed_file',
      '#title' => t('Choose a file for protected download'),
      '#size' => $form['protected_file']['#size'],
      '#multiple' => FALSE,
      '#theme_wrappers' => [],
      '#error_no_message' => TRUE,
      '#upload_location' => 'private://webform_protected_downloads/',
      '#default_value' => $webform_settings['protected_file'] ? [current($webform_settings['protected_file'])] : NULL,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $private_folder = \Drupal::service('file_system')->realpath('private://');
    if (!$private_folder) {
      drupal_set_message("Private files folder is not set! Please setup private folder to use this module correctly.", "error");
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement some form checks!
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the current webform entity.
    $params = \Drupal::routeMatch()->getParameters();
    $webform_id = $params->get('webform');
    $webform = Webform::load($webform_id);

    // Save/update settings.
    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      if ($key == 'submit' || $key == 'op') {
        continue;
      }
      $webform->setThirdPartySetting("webform_protected_downloads", $key, $value);
    }

    if ($values['enabled_protected_files'] == 0) {
      drupal_set_message(t("Make sure to also remove webform protected downloads token instances after disabling this."), "warning");
    }

    $webform->save();
    drupal_set_message(t("Settings saved."));
  }

}