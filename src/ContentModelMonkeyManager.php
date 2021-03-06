<?php

namespace Drupal\content_model_monkey;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\FieldConfigInterface;
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
    $content_type_rows            = $this->getContentTypeRowNumbersFromFieldsSheet();
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
    $content_model_type_definition  = $content_model_type_definitions[$type_name];
    $entity_type_manager    = \Drupal::entityTypeManager();

    $content_type_entity = $entity_type_manager->getStorage('node_type')->load($content_model_type_definition['base_type']);
    $existing_entity = $entity_type_manager->getStorage('node_type')->load($type_name);
    if ($existing_entity) {
      $existing_entity_uuid = $existing_entity->get('uuid');
      $existing_entity      = $content_type_entity->createDuplicate();
      $existing_entity->setOriginalId($content_model_type_definition['field_name']);
      $existing_entity->enforceIsNew(FALSE);
      $existing_entity->set('uuid', $existing_entity_uuid);
      $existing_entity->set('type', $type_name);
    }

    $properties = [
      'id'          => $content_model_type_definition['field_name'],
      'label'       => $content_model_type_definition['label'],
      'description' => $content_model_type_definition['description'] ?? '',
    ];

    $id_key    = $entity_type_manager->getDefinition('node_type')->getKey('id');
    $label_key = $entity_type_manager->getDefinition('node_type')->getKey('label');

    $existing_entity->set($id_key, $properties['id']);
    $existing_entity->set($label_key, $properties['label']);

    foreach ($properties as $key => $property) {
      $existing_entity->set($key, $property);
    }
    $existing_entity->save();

    $this->cloneFields($content_type_entity->id(), $existing_entity->id(), 'node');

    $view_displays = \Drupal::service('entity_display.repository')->getFormModes('node');
    $view_displays = array_merge($view_displays, ['default' => 'default']);
    if (!empty($view_displays)) {
      $this->cloneDisplays('form', $content_type_entity->id(), $existing_entity->id(), $view_displays, 'node');
    }

    $view_displays = \Drupal::service('entity_display.repository')->getViewModes('node');
    $view_displays = array_merge($view_displays, ['default' => 'default']);
    if (!empty($view_displays)) {
      $this->cloneDisplays('view', $content_type_entity->id(), $existing_entity->id(), $view_displays, 'node');
    }

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
    $more_fields             = TRUE;
    $content_type_definition = $this->getContentTypeDefinition($type);
    $row_number              = $content_type_definition['cm_row_no'] + 1;
    $fields                  = [];
    while ($more_fields) {
      $type_column_value = $this->fieldsSheet->getCell("B{$row_number}")
        ->getValue();
      if ($type_column_value === 'Field') {
        $fields[] = $this->getCmFieldDataFromRowNumber($row_number);
      }
      elseif (is_null($type_column_value)) {
        $more_fields = FALSE;
      }
      $row_number++;
    }

    foreach ($fields as $key => $field) {
      if ($field['required']) {
        $content_type_definition = $this->getContentTypeDefinition($type);
        $content_type_row_number = $content_type_definition['cm_row_no'] + 1;
        $fields[$key]['weight'] = $field['weight'] - $content_type_row_number;
      }
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

    echo "Add {$field['field_name']} to full view mode on $type_name\n";
    $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'full', 1);

    // @todo, this needs be another plugin or such.
    if ($field['field_name'] !== 'field_published_date') {
      echo "Add {$field['field_name']} to form view mode on $type_name\n";
      $cmm_field_plugin_instance->addToFormViewMode($type_name, $field);

      echo "Add {$field['field_name']} to search index view mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'search_index', 0);

    } else {
      $field['form_view_mode_group'] = '';
      echo "Add {$field['field_name']} to form view mode on $type_name\n";
      $cmm_field_plugin_instance->addToFormViewMode($type_name, $field);

      echo "Add {$field['field_name']} to search view mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'search', 0, 'date');

      echo "Published date: Add {$field['field_name']} to summary display mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'summary', 0, 'date');

      echo "Published date: Add {$field['field_name']} to teaser display mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'teaser', 0, 'date');

      echo "Published date: Add {$field['field_name']} to teaser - inline display mode on $type_name\n";
      $cmm_field_plugin_instance->addToDisplayViewMode($type_name, $field, 'teaser_inline', 0, 'date');

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
      $content_type                = [];
      $content_type['field_name']  = $this->stringUtils->trimPrefixFromString('type: ', $this->fieldsSheet->getCell("C{$row_number}")->getValue());
      $content_type['label']       = $this->fieldsSheet->getCell("D{$row_number}")->getValue();
      $content_type['description'] = $this->fieldsSheet->getCell("E{$row_number}")->getFormattedValue();
      if ($content_type['description'] === 'DONE' || $content_type['description'] === 'TODO') {
        $content_type['description'] = '';
      }
      $content_type['cm_row_no']                             = $row_number;
      $content_type['base_type']                             = $this->fieldsSheet->getCell("H{$row_number}")
          ->getValue() ?? 'base';
      $content_type_definitions[$content_type['field_name']] = $content_type;
    }

    return $content_type_definitions;
  }

  /**
   * Load and store the feeds sheet.
   */
  private function initialiseFieldsSheet(): void {
    $input_file_name   = \Drupal::service('file_system')
      ->realpath('public://TGA Content Model.xlsx');
    $spreadsheet       = @IOFactory::load($input_file_name);
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

    $cm_area = (string) $this->fieldsSheet->getCell("A{$row_number}")
      ->getValue();


    $field['field_name']           = (string) $this->fieldsSheet->getCell("C{$row_number}")->getValue();
    $field['label']                = (string) $this->fieldsSheet->getCell("D{$row_number}")->getValue();
    $field['description']          = (string) $this->fieldsSheet->getCell("E{$row_number}")->getValue();
    $field['type']                 = (string) $this->fieldsSheet->getCell("H{$row_number}")->getValue();
    $field['form_view_mode_group'] = $this->convertAreaToGroupName($cm_area);
    $field['required']             = (boolean) $this->fieldsSheet->getCell("F{$row_number}")->getCalculatedValue();
    $field['cardinality']          = (int) $this->fieldsSheet->getCell("G{$row_number}")
      ->getValue();
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

  protected function cloneDisplays($type, $entity_id, $cloned_entity_id, array $view_displays, $bundle_of) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $existing_displays = $this->getExistingDisplays($type, $cloned_entity_id, $view_displays, $bundle_of);
    foreach ($view_displays as $view_display_id => $view_display) {
      /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $display */
      $display = $entity_type_manager->getStorage('entity_' . $type . '_display')->load($bundle_of . '.' . $entity_id . '.' . $view_display_id);
      if ($display) {

        $cloned_view_display        = $display->createDuplicate();
        if (!in_array($view_display_id, array_keys($existing_displays))) {
          /** @var \Drupal\entity_clone\EntityClone\EntityCloneInterface $view_display_clone_handler */
          $view_display_clone_handler = $entity_type_manager->getHandler($entity_type_manager->getDefinition($display->getEntityTypeId())
            ->id(), 'entity_clone');
          $view_display_properties    = [
            'id' => $bundle_of . '.' . $cloned_entity_id . '.' . $view_display_id,
          ];
          $cloned_view_display->set('bundle', $cloned_entity_id);
          $view_display_clone_handler->cloneEntity($display, $cloned_view_display, $view_display_properties);
        }
        else {
          $id = $existing_displays[$view_display_id]->get('id');
          $uuid = $existing_displays[$view_display_id]->get('uuid');
          $existing_displays[$view_display_id] = clone $display;
          $existing_displays[$view_display_id]->set('id', $id);
          $existing_displays[$view_display_id]->set('originalId', $id);
          $existing_displays[$view_display_id]->set('uuid', $uuid);
          $existing_displays[$view_display_id]->setTargetBundle($cloned_entity_id);
          $existing_displays[$view_display_id]->save();
        }

      }
    }
  }

  protected function getExistingDisplays($type, $cloned_entity_id, array $view_displays, $bundle_of) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $displays = [];
    foreach ($view_displays as $view_display_id => $view_display) {
      /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $display */
      $display = $entity_type_manager->getStorage('entity_' . $type . '_display')->load($bundle_of . '.' . $cloned_entity_id . '.' . $view_display_id);
      if ($display) {
        $displays[$view_display_id] = $display;
      }
    }
    return $displays;
  }

  protected function cloneFields($entity_id, $cloned_entity_id, $bundle_of) {

    $entity_type_manager = \Drupal::entityTypeManager();

    /** @var \Drupal\Core\Entity\EntityFieldManager $field_manager */
    $field_manager = \Drupal::service('entity_field.manager');
    $fields = $field_manager->getFieldDefinitions($bundle_of, $entity_id);
    foreach ($fields as $field_definition) {
      if ($field_definition instanceof FieldConfigInterface) {
        if ($entity_type_manager->hasHandler($entity_type_manager->getDefinition($field_definition->getEntityTypeId()) ->id(), 'entity_clone')) {

          $cloned_entity_fields = $field_manager->getFieldDefinitions($bundle_of, $cloned_entity_id);
          $existing_fields = [];
          foreach ($cloned_entity_fields as $cloned_entity_field_definition) {
            if ($cloned_entity_field_definition instanceof FieldConfigInterface) {
              $old_id = str_replace(".$cloned_entity_id.", ".$entity_id.", $cloned_entity_field_definition->id());
              $existing_fields[$old_id] = $cloned_entity_field_definition;
            }
          }

          if (!in_array($field_definition->id(), array_keys($existing_fields))) {

            /** @var \Drupal\entity_clone\EntityClone\EntityCloneInterface $field_config_clone_handler */
            $field_config_clone_handler = $entity_type_manager->getHandler($entity_type_manager->getDefinition($field_definition->getEntityTypeId())->id(), 'entity_clone');
            $field_config_properties = [
              'id' => $field_definition->getName(),
              'label' => $field_definition->label(),
              'skip_storage' => TRUE,
            ];
            $cloned_field_definition = $field_definition->createDuplicate();
            $cloned_field_definition->set('bundle', $cloned_entity_id);
            $field_config_clone_handler->cloneEntity($field_definition, $cloned_field_definition, $field_config_properties);
          }
          else {
            $field_name = $field_definition->getName();
            $source_field_id = $field_definition->id();
            $existing_field_id = $existing_fields[$source_field_id]->id();
            $existing_field_uuid = $existing_fields[$source_field_id]->get('uuid');
            $existing_fields[$source_field_id] = clone $field_definition;
            $existing_fields[$source_field_id]->set('id', $existing_field_id);
            $existing_fields[$source_field_id]->set('originalId', $existing_field_id);
            $existing_fields[$source_field_id]->set('field_name',  $field_name);
            $existing_fields[$source_field_id]->set('uuid',  $existing_field_uuid);
            $existing_fields[$source_field_id]->set('bundle', $cloned_entity_id);
            $existing_fields[$source_field_id]->save();
          }

        }
      }

    }
  }
}
