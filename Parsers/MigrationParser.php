<?php

namespace Pingpong\Generators\Parsers;

use Illuminate\Contracts\Support\Arrayable;

class MigrationParser implements Arrayable {

	/**
	 * The array of custom migrtions.
	 * 
	 * @var array
	 */
	protected $customAttributes = [
		'remember_token' => 'rememberToken()',
		'soft_delete' => 'softDeletes()',
	];

	/**
	 * The migration schema.
	 *
	 * @var string
	 */
	protected $migration;

	/**
	 * Create new instance.
	 * 
	 * @param string|null $migration
	 */
	public function __construct($migration = null)
	{
		$this->migration = $migration;
	}

	/**
	 * Parse a string to array of formatted schema.
	 * 
	 * @param  string $migration]
	 * @return array
	 */
	public function parse($migration)
	{
		$this->migration = $migration;

		$parsed = [];

		foreach ($this->getSchemas() as $schema)
		{
			$column = $this->getColumn($schema);
			
			$attributes = $this->getAttributes($column, $schema);

			$parsed[$column] = $attributes;	
		}

		return $parsed;
	}

	public function getSchemas()
	{
		return explode(',', str_replace(' ', '', $this->migration));
	}

	/**
	 * Convert string migration to array.
	 * 
	 * @return array
	 */
	public function toArray()
	{
		return $this->parse($this->migration);
	}

	/**
	 * Render the migration to formatted script.
	 * 
	 * @return string
	 */
	public function render()
	{
		$results = '';

		foreach ($this->toArray() as $column => $attributes)
		{
			$results .= $this->createField($column, $attributes); 
		}

		return $results;
	}

	/**
	 * Create field.
	 * 
	 * @param  string $column
	 * @param  array $attributes
	 * @return string
	 */
	public function createField($column, $attributes)
	{
		$results = '$table';

		foreach ($attributes as $key => $field)
		{
			$results .= $this->formatField($key, $field, $column);
		}

		return $results .= ';'.PHP_EOL;
	}

	/**
	 * Format field to script.
	 * 
	 * @param  int $key
	 * @param  string $field
	 * @param  string $column
	 * @return string
	 */
	protected function formatField($key, $field, $column)
	{
		if ($this->hasCustomAttribute($column)) return '->' . $field;

		if ($key == 0)
		{
			return '->' . $field . "('". $column."')";	
		}

		return '->' . $field . '()';
	}

	/**
	 * Get column name from schema.
	 * 
	 * @param  string $schema
	 * @return string
	 */
	public function getColumn($schema)
	{
		return array_first(explode(':', $schema), function ($key, $value)
		{
			return $value;
		});
	}

	/**
	 * Get column attributes.
	 * 
	 * @param  string $column
	 * @param  string $schema
	 * @return array
	 */
	public function getAttributes($column, $schema)
	{
		$fields = str_replace($column.':', '', $schema);

		return $this->hasCustomAttribute($column) ? $this->getCustomAttribute($column) : explode(':', $fields);
	}

	/**
	 * Determinte whether the given column is exist in customAttributes array.
	 * 
	 * @param  string  $column
	 * @return boolean
	 */
	public function hasCustomAttribute($column)
	{
		return array_key_exists($column, $this->customAttributes);
	}

	/**
	 * Get custom attributes value.
	 * 
	 * @param  string $column
	 * @return array
	 */
	public function getCustomAttribute($column)
	{
		return (array) $this->customAttributes[$column];
	}

}