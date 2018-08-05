<?php

namespace Drupal\ts_generator\ComponentGenerator;


trait UnionGenerator {
  protected function generateUnionObject($types, $component = 'type', $single_line = FALSE) {
    $_types = [];

    foreach ($types as $type) {
      $_types[] = $type->getComponent($component);
    }

    return $single_line ? implode(' | ' , $_types) : (
      "(\n  " . implode(" |\n  ", $_types) . "\n)"
    );
  }

  protected function generateUnionParser($types, $type_name, $target_type_name, $single_line = FALSE) {
    $parserComponents = [];
    foreach ($types as $type) {
      $parserComponents[] = ($single_line ? '' : '  ') . $type->getComponent('guard') . '(t)';
      $parserComponents[] = ($single_line ? '' : '    ') . $type->getComponent('parser') . '(t)';
    }

    array_splice($parserComponents, -2 , 1);

    $parser = implode($single_line ? ' : ' : " :\n", array_map(function($a) use ($single_line) {
      return implode($single_line ? ' ? ' : " ?\n", $a);
    }, array_chunk($parserComponents, 2)));

    return '(t: ' . $type_name . '): ' . $target_type_name . ($single_line ? " => " : " =>\n") . $parser;
  }

  protected function generateUnionGuard($types, $type_name, $guarded_type_name, $enforced_type_name = NULL) {
    $guardComponents = [];
    foreach ($types as $type) {
      $guardComponents[] = '  ' . $type->getComponent('guard') . '(t' . ($enforced_type_name ? (' as ' . $enforced_type_name) : '') . ')';
    }

    return '(t: ' . $type_name . '): t is ' . $guarded_type_name . " => (\n" . implode(" ||\n", $guardComponents) . "\n)";
  }
}