<?php

namespace Drupal\ts_generator\ComponentGenerator;

use Drupal\Component\Uuid\Com;
use Drupal\ts_generator\ComponentResult;
use Drupal\ts_generator\Result;
use Drupal\ts_generator\Settings;

trait PropertiesGenerator {
  use NoopParserGenerator;

  /**
   * @param \Drupal\ts_generator\ComponentResultInterface|string[] $properties
   * @param string $component
   * @return string
   */
  protected function generatePropertiesObject(array $properties, $mapping = NULL) {
    if (!isset($mapping)) {
      $mapping = $this->getDefaultMapping($properties);
    }

    $combinators = [];

    if (!is_string($mapping)) {
      foreach ($mapping as $property_target_key => $property_key) {
        if (!is_numeric($property_target_key)) {
          continue;
        }

        if (is_string($property_key) && isset($properties[$property_key])) {
          continue;
        }

        if (is_string($property_key)) {
          $combinators[] = $property_key;
        } else {
          $combinators[] = $property_key->getComponent('type');
        }
      }
    }

    $result_properties = [];
    foreach ($properties as $key => $property) {
      $result_properties[$key] = $this->generatePropertyType($properties, $key, 'type');
    }

    return $this->formatObject($combinators, $result_properties);
  }

  protected function formatObject($combinators, $result_properties) {
    $_result_properties = [];

    foreach ($result_properties as $key => $value) {
      $optional = FALSE;
      if (substr($key, -1, 1) == '?') {
        $key = substr($key, 0, -1);
        $optional = TRUE;
      }

      if (!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*$/', $key)) {
        $key = json_encode($key);
      }

      $_result_properties[] = '  ' . $key . ($optional ? '?' : '') . ': ' . $value;
    }

    return ($combinators || $_result_properties) ? implode(' & ' , array_filter([
      ($combinators ? (implode(' & ', $combinators)) : ''),
      ($_result_properties ? "{\n" . implode(",\n", $_result_properties) . "\n}" : '')
    ])) : '{}';
  }

  protected function generatePropertyType(array $properties, $property_key, $component = 'type') {
    $property_data = is_string($properties[$property_key]) ? $properties[$property_key] : $properties[$property_key]->getComponent($component);
    $property_data = $this->cleanupPropertyType($property_data);
    return $property_data;
  }

  protected function getDefaultMapping(array $properties, $mapping = NULL) {
    return array_combine(array_keys($properties), array_keys($properties));
  }

  protected function generatePropertiesTargetObject(array $properties, $mapping = NULL) {
    if (!isset($mapping)) {
      $mapping = $this->getDefaultMapping($properties);
    }

    if (is_string($mapping)) {
      if (isset($properties[$mapping])) {
        return $this->generatePropertyType($properties, $mapping, 'target_type');
      } else {
        $mapping = [$mapping];
      }
    }

    $result = [];
    $combinators = [];

    foreach ($mapping as $property_target_key => $property_key) {
      if (is_numeric($property_target_key)) {
        if (is_string($property_key) && isset($properties[$property_key])) {
          $result[$property_key] = $this->generatePropertyType($properties, $property_key, 'target_type');
        } else {
          if (is_string($property_key)) {
            $combinators[] = $property_key;
          } else {
            $combinators[] = $property_key->getComponent('target_type');
          }
        }
      } else {
        if (is_string($property_key) && isset($properties[$property_key])) {
          $result[$property_target_key] = $this->generatePropertyType($properties, $property_key, 'target_type');
        } else {
          if (is_string($property_key)) {
            $result[$property_target_key] = $property_key;
          } else {
            $result[$property_target_key] = $property_key->getComponent('target_type');
          }
        }
      }
    }

    return $this->formatObject($combinators, $result);
  }

  protected function cleanupPropertyType($type) {
    $_type = explode(' | ', $type);
    $_uniq_type = array_unique($_type);

    return implode(' | ', $_uniq_type);
  }

  protected function generatePropertyParser(array $properties, $property_key) {
    if (is_string($properties[$property_key])) {
      if (!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*$/', $property_key)) {
        return 't[' . json_encode($property_key) . ']';
      } else {
        return 't.' . $property_key;
      }
    } else {
      if (!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*$/', $property_key)) {
        return $properties[$property_key]->getComponent('parser') . '(t[' . json_encode($property_key) . '])';
      } else {
        return $properties[$property_key]->getComponent('parser') . '(t.' . $property_key . ')';
      }
    }
  }

  protected function generatePropertyParserLine($key, $value) {
    if (!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*$/', $key)) {
      $key = json_encode($key);
    }

    return '  ' . $key . ': ' . $value;
  }

  /**
   * @param \Drupal\ts_generator\ComponentResultInterface|string[] $properties
   * @param null|string|array $mapping
   * @param string $type_name
   * @param string $target_type_name
   * @return string
   */
  protected function generatePropertiesParser(array $properties, $type_name, $target_type_name, $mapping = NULL) {
    return '(t: ' . $type_name . '): ' . $target_type_name . " => " . $this->generatePropertiesParserContent($properties, $mapping);
  }

  protected function generatePropertiesParserContent(array $properties, $mapping = NULL) {
    if (!isset($mapping)) {
      $mapping = $this->getDefaultMapping($properties);
    }

    if (is_string($mapping)) {
      if (isset($properties[$mapping])) {
        return $this->generatePropertyParser($properties, $mapping);
      } else {
        $mapping = [$mapping];
      }
    }

    $result = [];

    foreach ($mapping as $property_target_key => $property_key) {
      if (is_numeric($property_target_key)) {
        if (is_string($property_key) && isset($properties[$property_key])) {
          $result[] = $this->generatePropertyParserLine($property_key, $this->generatePropertyParser($properties, $property_key));
        } else {
          if (is_string($property_key)) {
            $result[] = '  ...' . $property_key . '(t)';
          } else {
            $result[] = '  ...' . $property_key->getComponent('parser') . '(t)';
          }
        }
      } else {
        if (is_string($property_key) && isset($properties[$property_key])) {
          $result[] = $this->generatePropertyParserLine($property_target_key, $this->generatePropertyParser($properties, $property_key));
        } else {
          if (is_string($property_key)) {
            $result[] = $this->generatePropertyParserLine($property_target_key, $property_key . '(t)');
          } else {
            $result[] = $this->generatePropertyParserLine($property_target_key, $property_key->getComponent('parser') . '(t)');
          }
        }
      }
    }

    return "({\n" . implode(",\n", $result) . "\n})";
  }

  protected function generatePropertiesComponentResult($properties, $type_name, $target_type_name, $parser_name, $mapping, Settings $settings, Result $result) {
    if (!isset($mapping)) {
      $mapping = $this->getDefaultMapping($properties);
    }

    $componentResult = new ComponentResult();

    $type = $componentResult->setComponent(
      'type',
      $result->setComponent(
        'types/' . $type_name,
        'type ' . $type_name . ' = ' . $this->generatePropertiesObject($properties, $mapping)
      )
    );

    if ($settings->generateParser()) {
      $target_type = $componentResult->setComponent(
        'target_type',
        !is_string($mapping) ? $result->setComponent(
          'types/' . $target_type_name,
          'type ' . $target_type_name . ' = ' . $this->generatePropertiesTargetObject($properties, $mapping)
        ) : $this->generatePropertiesTargetObject($properties, $mapping)
      );

      $parser = $componentResult->setComponent(
        'parser',
        $result->setComponent(
          'parser/' . $parser_name,
          'const ' . $parser_name . ' = ' . $this->generatePropertiesParser($properties, $type, $target_type, $mapping)
        )
      );
    }

    return $componentResult;
  }
}
