<?php

namespace Drupal\content_model_monkey\Commands;

use Drupal\content_model_monkey\ContentModelMonkeyManager;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class ContentModelMonkeyCommands extends DrushCommands {

  /**
   * Content Model Monkey Manager.
   *
   * Interacts with the content model monkey.
   *
   * @var \Drupal\content_model_monkey\ContentModelMonkeyManager
   */
  private $contentModelMonkeyManager;

  /**
   * Construct a new ContentModelMonkeyCommands object.
   *
   * @param \Drupal\content_model_monkey\ContentModelMonkeyManager $content_model_monkey_manager
   *   Content Model Monkey Manager.
   */
  public function __construct(ContentModelMonkeyManager $content_model_monkey_manager) {
    parent::__construct();
    $this->contentModelMonkeyManager = $content_model_monkey_manager;
  }

  /**
   * Display the fields defined for a type in the feeds sheet.
   *
   * @param string $type_name
   *   The type of which to display content model fields.
   *
   * @usage cmm:show_fields type
   *   Show the fields for type.
   *
   * @command cmm:show_fields
   * @aliases cmsf
   */
  public function cmShowFieldsForType(string $type_name) {
    $entity_storage_creator = new ContentModelMonkeyManager();
    $fields = $entity_storage_creator->getFieldDefinitionsOfType($type_name);
    var_export($fields);
    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Create content type based off definition in feeds sheet.
   *
   * Given the type_name, create a content type in drupal by using entity_clone
   * to clone the base type and provide the new type with a new name,
   * description etc.
   *
   * @param string $type_names
   *   The machine name of the content type to create.
   *
   * @usage cmm:create_type type_name
   *   Usage description
   *
   * @command cmm:create_type
   * @aliases cmct
   */
  public function cmCreateType(string $type_names) {
    $type_names = explode(',', $type_names);
    foreach ($type_names as $type_name) {
      $this->contentModelMonkeyManager->createType($type_name);
    }
    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Create content type based off definition in feeds sheet.
   *
   * Given the type_name, create a content type in drupal by using entity_clone
   * to clone the base type and provide the new type with a new name,
   * description etc.
   *
   * @param string $type_names
   *   The machine name of the content type to create.
   *
   * @usage cmm:create_type type_name
   *   Usage description
   *
   * @command cmm:create_type_with_fields
   * @aliases cmctwf
   */
  public function cmCreateTypeWithFields(string $type_names) {
    $type_names = explode(',', $type_names);
    foreach ($type_names as $type_name) {
      echo "Creating type: $type_name\n";
      $this->contentModelMonkeyManager->createType($type_name);

      echo "Creating fields for $type_name\n";
      echo "===============================\n\n";
      $this->contentModelMonkeyManager->createFieldsForType($type_name);
    }
    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Create content type based off definition in feeds sheet.
   *
   * Given the type_name, create a content type in drupal by using entity_clone
   * to clone the base type and provide the new type with a new name,
   * description etc.
   *
   * @param string $type_names
   *   The machine name of the content type to create.
   *
   * @usage cmm:create_type type_name
   *   Usage description
   *
   * @command cmm:delete_types
   * @aliases cmdt
   */
  public function cmDeleteTypes(string $type_names) {
    $entity_type_manager = \Drupal::entityTypeManager();

    $type_names = explode(',', $type_names);
    foreach ($type_names as $type_name) {
      $content_type_entity = $entity_type_manager->getStorage('node_type')->load($type_name);
      if (!is_null($content_type_entity)) {
        $content_type_entity->delete();
      }
    }

    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Add fields from content model to content type in drupal.
   *
   * @param string $type_names
   *   The machine name of the content type to create.
   *
   * @usage cmm:create_type_fields type_name1,type_name2
   *   Usage description
   *
   * @command cmm:create_type_fields
   * @aliases cmctf
   */
  public function cmCreateTypeFields(string $type_names) {
    $type_names = explode(',', $type_names);
    foreach ($type_names as $type_name) {
      echo "Creating fields for $type_name\n";
      echo "===============================\n\n";
      $this->contentModelMonkeyManager->createFieldsForType($type_name);
    }
    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Checks the fields in the content model have valid definitions.
   *
   * @usage cmm:validate
   *   Usage description
   *
   * @command cmm:validate
   * @aliases cmv
   */
  public function validate() {
    $content_types = $this->contentModelMonkeyManager->getContentTypeDefinitionsFromFieldsSheet();
    foreach ($content_types as $content_type) {
      $fields = $this->contentModelMonkeyManager->getFieldDefinitionsOfType($content_type['machine_name']);
      foreach ($fields as $field) {
        if ($field['error']) {
          $this->logger()->error(dt($field['error']));
        }
      }
    }
    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Command description here.
   *
   * @usage cmm-commandName foo
   *   Usage description
   *
   * @command cmm:report
   * @aliases cmr
   */
  public function report() {
    $content_types = $this->contentModelMonkeyManager->getContentTypeDefinitionsWithFieldDataFromFieldsSheet();
    foreach ($content_types as $content_type) {
      print_r($content_type);
    }

    $this->logger()->success(dt('Achievement unlocked.'));
  }

}
