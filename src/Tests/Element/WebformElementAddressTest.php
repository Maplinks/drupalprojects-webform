<?php

namespace Drupal\webform\Tests\Element;

use Drupal\address\LabelHelper;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform address element.
 *
 * @group Webform
 */
class WebformElementAddressTest extends WebformElementTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'address', 'node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_address'];

  /**
   * Tests address element.
   */
  public function testAddress() {
    $webform = Webform::load('test_element_address');

    /**************************************************************************/
    // Processing.
    /**************************************************************************/


    // Check submit.
    $this->postSubmission($webform);
    $this->assertRaw("address:
  country_code: US
  langcode: en
  given_name: John
  family_name: Smith
  organization: 'Google Inc.'
  address_line1: '1098 Alta Ave'
  address_line2: ''
  locality: 'Mountain View'
  administrative_area: CA
  postal_code: '94043'
  additional_name: null
  sorting_code: null
  dependent_locality: null
address_advanced:
  country_code: US
  langcode: en
  address_line1: '1098 Alta Ave'
  address_line2: ''
  locality: 'Mountain View'
  administrative_area: CA
  postal_code: '94043'
  given_name: null
  additional_name: null
  family_name: null
  organization: null
  sorting_code: null
  dependent_locality: null
address_none: null");

    /**************************************************************************/
    // Schema.
    /**************************************************************************/

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'address',
      'type' => 'address',
    ]);
    $schema = $field_storage->getSchema();

    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    /** @var \Drupal\webform\Plugin\WebformElement\Address $element_plugin */
    $element_plugin = $element_manager->getElementInstance(['#type' => 'address']);

    // Get webform address element plugin.
    $element = [];
    $element_plugin->initializeCompositeElements($element);;

    // Check composite elements against address schema.
    $composite_elements = $element['#webform_composite_elements'] ;
    $diff_composite_elements = array_diff_key($composite_elements, $schema['columns']);
    $this->debug($diff_composite_elements);
    $this->assert(empty($diff_composite_elements));

    // Check composite elements maxlength against address schema.
    foreach ($schema['columns'] as $column_name => $column) {
      $this->assertEqual($composite_elements[$column_name]['#maxlength'], $column['length']);
    }

    // Check used fields against address schema.
    $diff_used_fields = array_diff_key($element_plugin->getDefaultProperty('used_fields'), LabelHelper::getGenericFieldLabels());
    $this->debug($diff_used_fields);
    $this->assert(empty($diff_used_fields));
  }

}
