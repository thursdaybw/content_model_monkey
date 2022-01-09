<?php

namespace Drupal\content_model_monkey;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class for validating and creating data strucures from the content model.
 */
class ContentModelMonkeyManager implements ContainerInjectionInterface {

  /**
   * Plugin manager for accessing Content Model Monkey Field plugins.
   *
   * @var \Drupal\content_model_monkey\ContentModelMonkeyFieldPluginManager
   */
  private $fieldPluginManager;

  /**
   * Factory method to create a new ContentModelMonkeyManager.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The drupal container, used to inject dependent services.
   *
   * @return static
   *   A newly instantiated ContentModelMonkeyManager object.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.content_model_monkey_field'),
    );
  }

  /**
   * Create a new ContentModelMonkeyManager object.
   *
   * @param \Drupal\content_model_monkey\ContentModelMonkeyFieldPluginManager $field_plugin_manager
   *   Plugin manager for accessing Content Model Monkey Field plugins.
   */
  public function __construct(ContentModelMonkeyFieldPluginManager $field_plugin_manager) {
    $this->stringUtils = new StringUtils();

    $this->initialiseFieldsSheet();
    $content_type_rows = $this->getContentTypeRowNumbersFromFieldsSheet();
    $this->contentTypeDefinitions = $this->getContentTypeDefinitionsFromRows($content_type_rows);

    $this->fieldPluginManager = $field_plugin_manager;
  }

  /**
   * Get the array of content type definitions.
   *
   * @return array
   *   An array of content type definitions.
   */
  public function getContentTypeDefinitionsFromFieldsSheet() {
    return $this->contentTypeDefinitions;
  }

  /**
   * Get the array of content type definitions along with field data.
   *
   * @return array
   *   An array of content type definitions and field data.
   */
  public function getContentTypeDefinitionsWithFieldDataFromFieldsSheet() {
    $content_type_definitions = $this->contentTypeDefinitions;
    foreach ($this->contentTypeDefinitions as $content_type_name => $content_type_definition) {
      $content_type_definitions[$content_type_name]['fields'] = $this->getFieldDefinitionsOfType($content_type_name);
    }
    return $content_type_definitions;
  }

  /**
   * Get the definition data of a type based off content model.
   *
   * @param string $type_name
   *   The machine name of the type to retrieve.
   *
   * @return array
   *   An array containing definitions data of the content type.
   */
  public function getContentTypeDefinition(string $type_name) {
    return $this->contentTypeDefinitions[$type_name];
  }

  /**
   * Create a content type as defined in the fields sheet of the content model.
   *
   * @param string $type_name
   *   The machine name of the type to create.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createType(string $type_name): void {

    $content_model_type_definitions = $this->getContentTypeDefinitionsFromFieldsSheet();
    $content_model_type_definition = $content_model_type_definitions[$type_name];

    $entity_type_manager = \Drupal::entityTypeManager();

    $entity_type_definition = $entity_type_manager->getDefinition('node_type');
    $content_type_entity = $entity_type_manager->getStorage('node_type')->load($content_model_type_definition['base_type']);

    $entity_clone_handler = \Drupal::entityTypeManager()->getHandler($entity_type_definition->id(), 'entity_clone');

    $properties = [
      'id' => $content_model_type_definition['field_name'],
      'label' => $content_model_type_definition['label'],
      'description' => $content_model_type_definition['description'] ?? "no description",
    ];
    $duplicate = $content_type_entity->createDuplicate();

    $entity_clone_handler->cloneEntity($content_type_entity, $duplicate, $properties);

  }

  /**
   * Get the field definitions of a content type base of data in content model.
   *
   * Some data is transliterated from Murray speak to Drupal speak.
   *
   * @param string $type
   *   The machine name of the content type to load.
   *
   * @return array
   *   An array of field definitions.
   */
  public function getFieldDefinitionsOfType(string $type) {
    $more_fields = TRUE;
    $content_type_definition = $this->getContentTypeDefinition($type);
    $row_number = $content_type_definition['cm_row_no'] + 1;
    $fields = [];
    while ($more_fields) {
      $type_column_value = $this->fieldsSheet->getCell("B{$row_number}")->getValue();
      if ($type_column_value === 'Field') {
        $fields[] = $this->getCmFieldDataFromRowNumber($row_number);
      }
      elseif (is_null($type_column_value)) {
        $more_fields = FALSE;
      }
      $row_number++;
    }
    return $fields;
  }

  /**
   * Create the fields defined in the content model for a type on that type.
   *
   * This creates the field, adds it to the relevant form and view modes.
   *
   * @param string $type_name
   *   The machine name of the content type to create a field for.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createFieldsForType(string $type_name): void {

    $fields = $this->getFieldDefinitionsOfType($type_name);
    foreach ($fields as $field) {
      $this->createFieldInstance($type_name, $field);
    }
  }

  /**
   * Create a field instance.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createFieldInstance($type_name, $field) {
    $field_storage = FieldStorageConfig::loadByName('node', $field['field_name']);

    if ($this->stringUtils->strStartsWith($field['type'], 'ref@')) {
      $plugin_id = 'Entity reference';
    }
    else {
      $plugin_id = $field['type'];
    }

    $cmm_field_plugin_instance = $this->fieldPluginManager->createInstance($plugin_id, ['cmField' => $field]);

    if (!$field_storage) {
      echo "Creating field storage for {$field['field_name']}\n";
      $field_storage = FieldStorageConfig::create([
        'field_name'  => $field['field_name'],
        'entity_type' => 'node',
        'type'        => $cmm_field_plugin_instance->getFieldType(),
      ]);

    }
    $field_storage->set('settings', $cmm_field_plugin_instance->getFieldStorageSettings());
    $field_storage->save();

    $field_config = FieldConfig::loadByName('node', $type_name, $field['field_name']);
    if (!$field_config) {
      echo "Creating field instance config for {$field['field_name']} on $type_name\n";
      $field_config = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle'        => $type_name,
      ]);
    }
    $field_config->setLabel($field['label']);
    $field_config->setRequired($field['required']);

    $field_config_settings = $cmm_field_plugin_instance->getFieldConfigSettings();
    $field_config->setSettings($field_config_settings);
    $field_config->save();

    // @todo, this needs be another plugin or such.
    echo "Add {$field['field_name']} to form view mode on $type_name\n";
    $cmm_field_plugin_instance->addToFormViewMode($type_name, $field);

    echo "Add {$field['field_name']} to full view mode on $type_name\n";
    $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'full', 1);

    if ($field['field_name'] === 'field_published_date') {

      echo "Add {$field['field_name']} to search view mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'search', 0, 'date');

      echo "Published date: Add {$field['field_name']} to summary display mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'summary', 0, 'date');

      echo "Published date: Add {$field['field_name']} to teaser display mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'teaser', 0, 'date');

      echo "Published date: Add {$field['field_name']} to teaser - inline display mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'teaser_inline', 0, 'date');

    }
    else {
      echo "Add {$field['field_name']} to search index view mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'search_index', 0);
    }

  }

  /**
   * Get the row numbers of each content type definition in the feeds sheet.
   *
   * @return array
   *   An array of row numbers.
   */
  private function getContentTypeRowNumbersFromFieldsSheet() {
    $content_type_rows = [];
    foreach ($this->fieldsSheet->getRowIterator() as $row_number => $row) {
      if ($row_number > 599) {
        break;
      }
      $cell_iterator = $row->getCellIterator();
      $cell_iterator->setIterateOnlyExistingCells(FALSE);

      foreach ($cell_iterator as $cell_key => $cell) {
        if ($cell_key === "B" && !empty($cell->getValue()) && $cell->getValue() === 'Node') {
          $content_type_rows[] = $row_number;
        }
      }
    }
    return $content_type_rows;
  }

  /**
   * For each row number in the array, read content type info from that row.
   *
   * @param array $content_type_rows
   *   An array of row numbers from which to read content type information.
   *
   * @return array
   *   An array of content type definitions from the content model.
   */
  private function getContentTypeDefinitionsFromRows(array $content_type_rows) {
    $content_type_definitions = [];
    foreach ($content_type_rows as $row_number) {
      $content_type = [];
      $content_type['field_name'] = $this->stringUtils->trimPrefixFromString('type: ', $this->fieldsSheet->getCell("C{$row_number}")->getValue());
      $content_type['label'] = $this->fieldsSheet->getCell("D{$row_number}")->getValue();
      $content_type['description'] = $this->fieldsSheet->getCell("E{$row_number}")->getValue();
      if ($content_type['description'] === 'DONE' || $content_type['description'] === 'TODO') {
        $content_type['description'] = '';
      }
      $content_type['cm_row_no'] = $row_number;
      $content_type['base_type'] = $this->fieldsSheet->getCell("H{$row_number}")->getValue() ?? 'base';
      $content_type_definitions[$content_type['field_name']] = $content_type;
    }

    return $content_type_definitions;
  }

  /**
   * Load and store the feeds sheet.
   */
  private function initialiseFieldsSheet(): void {
    $input_file_name = \Drupal::service('file_system')->realpath('public://TGA Content Model.xlsx');
    $spreadsheet = @IOFactory::load($input_file_name);
    $this->fieldsSheet = $spreadsheet->getSheetByName('Fields');
  }

  /**
   * Gather the relevant field data from a row in the feeds sheet.
   *
   * @param int $row_number
   *   The row to gather the data from.
   *
   * @return array
   *   The field data as read from the row in the fields sheet.
   */
  private function getCmFieldDataFromRowNumber(int $row_number): array {

    $cm_area = (string) $this->fieldsSheet->getCell("A{$row_number}")->getValue();


    $field['field_name']           = (string) $this->fieldsSheet->getCell("C{$row_number}")->getValue();
    $field['label']                = (string) $this->fieldsSheet->getCell("D{$row_number}")->getValue();
    $field['description']          = (string) $this->fieldsSheet->getCell("E{$row_number}")->getValue();
    $field['type']                 = (string) $this->fieldsSheet->getCell("H{$row_number}")->getValue();
    $field['form_view_mode_group'] = $this->convertAreaToGroupName($cm_area);
    $field['required']             = (boolean) $this->fieldsSheet->getCell("F{$row_number}")->getCalculatedValue();
    $field['cardinality']          = (int) $this->fieldsSheet->getCell("G{$row_number}")->getValue();
    $field['weight']               = $row_number;
    return $field;
  }

  private function convertAreaToGroupName($value) {
    if ($value === 'Meta') {
      return 'meta';
    }

    if ($value === 'Content') {
      return 'content';
    }

    if ($value === 'Downloadable files') {
      return 'attach';
    }

    return 'content';
  }

}
