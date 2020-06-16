# TS Generator

This is a Drupal module, that generates Typescript type definitions for certain entities. It can optionally also generate cleaned up target type definitions and functions to convert objects from the initials types to the target types (parsers).

## Installation

```sh
composer require hoppinger/ts_generator
```

## Versions

Version 2.0.0 is Drupal 9 compatible.

## Usage

The generator runs as a Drush command that needs a configuration file. 

Create `.yml` file with the following contents:

```yaml
target_directory: client/generated
entities:
  input:
    - node
    - taxonomy_term
generate_parser: true
```

This file instructs the generator to generate the files in a directory `client/generated` (relative to the location of the configuration file), to generate types for the `node` and `taxonomy_term` entities and to generate target types and corresponding parsers.

Trigger the generation using

```sh
cd [PROJECT DIRECTORY]/web
../vendor/bin/drush ts_generator:generate [PATH TO CONFIGURATION FILE]
```

## Actual usage of the types

This is an example of how you could use those types. You are not limited to this approach, of course.

```ts
import {
  InputEntity,
  ParsedInputEntity,
} from "./generated/types"

import {
  input_entity_parser,
  input_entity_guard
} from "./generated/parser"

export type Result<T> = T | "error"

export async function drupalGetEntity(alias: string): Promise<Result<ParsedInputEntity>> {
  const res = await fetch(`/${alias}?format=_json`), { method: "get", credentials: "include" })
  if (!res.ok) return "error"
  
  const json = await res.json()
  if (!input_entity_guard(json)) return "error"

  const parsed = input_entity_parser(json as InputEntity)
  return parsed
}
```

## Todo

* Write more documentation on usage
* Write documentation on the internal workings and ways to extend the generator
