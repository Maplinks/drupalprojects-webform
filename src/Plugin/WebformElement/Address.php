<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'address' element.
 *
 * @WebformElement(
 *   id = "address",
 *   label = @Translation("Advanced address"),
 *   description = @Translation("Provides advanced element for storing, validating and displaying international postal addresses."),
 *   category = @Translation("Composite elements"),
 *   composite = TRUE,
 *   multiline = TRUE,
 *   dependencies = {
 *     "address",
 *   }
 * )
 *
 * @see \Drupal\address\Element\Address
 */
class Address extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'available_countries' => [],
      'used_fields' => [
        'administrativeArea' => 'administrativeArea',
        'locality' => 'locality',
        'dependentLocality' => 'dependentLocality',
        'postalCode' => 'postalCode',
        'sortingCode' => 'sortingCode',
        'addressLine1' => 'addressLine1',
        'addressLine2' => 'addressLine2',
        'organization' => 'organization',
        'givenName' => 'givenName',
        'additionalName' => 'additionalName',
        'familyName' => 'familyName',
      ],
      'langcode_override' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [
      'given_name' => [
        '#title' => $this->t('Given name'),
      ],
      'family_name' => [
        '#title' => $this->t('Family name'),
      ],
      'organization' => [
        '#title' => $this->t('Organization'),
      ],
      'address_line1' => [
        '#title' => $this->t('Address line 1'),
      ],
      'address_line2' => [
        '#title' => $this->t('Address line 2'),
      ],
      'postal_code' => [
        '#title' => $this->t('Postal code'),
      ],
      'locality' => [
        '#title' => $this->t('Locality'),
      ],
      'administrative_area' => [
        '#title' => $this->t('Administrative area'),
      ],
      'country_code' => [
        '#title' => $this->t('Country code'),
      ],
      'langcode' => [
        '#title' => $this->t('Language code'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if ($format === 'value') {
      return $this->buildAddress($element, $webform_submission);
    }
    else {
      return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }


  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if ($format === 'value') {
      $build = $this->buildAddress($element, $webform_submission);
      $html = \Drupal::service('renderer')->renderPlain($build);
      return MailFormatHelper::htmlToText($html);
    }
    else {
      return parent::formatTextItem($element, $webform_submission, $options);
    }
  }

  /**
   * Build formatted address.
   *
   * The below code is copied form the protected
   * AddressDefaultFormatter::viewElements method.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   A render array containing the formatted address.
   *
   * @see \Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::viewElements
   * @see \Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::viewElement
   */
  protected function buildAddress(array $element, WebformSubmissionInterface $webform_submission) {
    /** @var \CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface $address_format_repository */
    $address_format_repository = \Drupal::service('address.address_format_repository');
    /** @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository */
    $country_repository = \Drupal::service('address.country_repository');

    $value = $this->getValue($element, $webform_submission);
    if (empty($value)) {
      return [];
    }

    $build = [
      '#prefix' => '<p class="address" translate="no">',
      '#suffix' => '</p>',
      '#post_render' => [
        ['\Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter', 'postRender'],
      ],
      '#cache' => [
        'contexts' => [
          'languages:' . LanguageInterface::TYPE_INTERFACE,
        ],
      ],
    ];

    $country_code = $value['country_code'];
    $countries = $country_repository->getList();
    $address_format = $address_format_repository->get($country_code);
    $build['address_format'] = [
      '#type' => 'value',
      '#value' => $address_format,
    ];
    // Hard coding the locale.
    $build['locale'] = [
      '#type' => 'value',
      '#value' => 'und',
    ];
    $build['country_code'] = [
      '#type' => 'value',
      '#value' => $country_code,
    ];
    $build['country'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => ['class' => ['country']],
      '#value' => Html::escape($countries[$country_code]),
      '#placeholder' => '%country',
    ];
    $used_fields = $this->getElementProperty($build, 'used_fields');
    foreach ($used_fields as $field) {
      $property = FieldHelper::getPropertyName($field);
      $class = str_replace('_', '-', $property);
      $build[$property] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => [$class]],
        '#value' => Html::escape($value[$property]),
        '#placeholder' => '%' . $field,
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Address settings'),
    ];

    /**************************************************************************/
    // Copied from: \Drupal\address\Plugin\Field\FieldType\AddressItem::fieldSettingsForm
    /**************************************************************************/

    $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    $language_options = [];
    foreach ($languages as $langcode => $language) {
      if (!$language->isLocked()) {
        $language_options[$langcode] = $language->getName();
      }
    }

    $form['address']['available_countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Available countries'),
      '#description' => $this->t('If no countries are selected, all countries will be available.'),
      '#options' => \Drupal::service('address.country_repository')->getList(),
      '#multiple' => TRUE,
      '#size' => 10,
    ];
    WebformElementHelper::enhanceSelect($form['address']['available_countries'], TRUE);
    $form['address']['used_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Used fields'),
      '#description' => $this->t('Note: an address used for postal purposes needs all of the fields.'),
      '#required' => TRUE,
      '#options' => LabelHelper::getGenericFieldLabels(),
    ];
    $form['address']['langcode_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Language override'),
      '#description' => $this->t('Ensures entered addresses are always formatted in the same language.'),
      '#options' => $language_options,
      '#empty_option' => $this->t('- No override -'),
      '#access' => \Drupal::languageManager()->isMultilingual(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return [
      [
        'given_name' => 'John',
        'family_name' => 'Smith',
        'organization' => 'Google Inc.',
        'address_line1' => '1098 Alta Ave',
        'postal_code' => '94043',
        'locality' => 'Mountain View',
        'administrative_area' => 'CA',
        'country_code' => 'US',
        'langcode' => 'en',
      ],
    ];
  }

}
