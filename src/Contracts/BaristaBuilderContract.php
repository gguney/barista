<?php

namespace GGuney\Barista\Contracts;

interface BaristaBuilderContract
{

    /**
     * Open a a new form.
     *
     * @param  array $array
     * @param  array $attributes
     *
     * @return string
     */
    public static function open(array $array , array $attributes = null): string;

    /**
     * Close a form.
     *
     * @param $attributes
     *
     * @return string
     */
    public static function close(): string;

    /**
     * Close a form with button.
     *
     * @param $attributes
     *
     * @return string
     */
    public static function closeWithButton(array $attributes): string;

    /**
     * Build Form From a DataModel
     *
     * @param  DataModel $dataModel
     * @param  Model $item
     * @param  array $errors
     *
     * @return string
     */
    public static function buildFromDM($dataModel, $item = null, $errors);

    /**
     * Detect input type.
     *
     * @param  DataModel $dataModel
     * @param  array $attributes
     * @param  string $formField
     * @param  Model $item
     *
     * @return string
     */
    public static function detectInput($dataModel, $attributes, $item);

    /**
     * Build show partial from DataModel
     *
     * @param  DataModel $dataModel
     * @param  Model $item
     *
     * @return string
     */
    public static function buildShowFromDM($dataModel, $item);

    /**
     * Build table partial from DataModel
     *
     * @param  DataModel $dataModel
     * @param  Model $items
     *
     * @return string
     */
    public static function buildTableFromDM($dataModel, $items);

    /**
     * Generate an HTML checkbox element.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function checkbox($name, $value, $attributes = null);

    /**
     * Generate an HTML file input with path and url.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function file($name, $value, $attributes = null);

    /**
     * Generate an HTML input element
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function input($name, $value, $attributes = null);

    /**
     * Generate an HTML textarea element.
     *
     * @param  string $name
     * @param  string $value
     * @param  string $attributes
     *
     * @return string
     */
    public static function textarea($name, $value, $attributes = null);

    /**
     * Generate an HTML select element with given options.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     * @param  array $options
     *
     * @return string
     */
    public static function select($name, $value, $attributes = null);

    /**
     * Generate an option for an HTML select.
     *
     * @param  string $name
     * @param  string $value
     * @param  string $selected
     *
     * @return string
     */
    public static function option($name, $value, $selected);

    /**
     * Generate an HTML link.
     *
     * @param  string $url
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function link($url, $value, $attributes);

    /**
     * Generate an HTML label.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function label($name, $value, $attributes = null);

    /**
     * Generate required tag.
     *
     * @return string
     */
    public static function required();

    /**
     * Generate error block.
     *
     * @param  string $name
     * @param  array $attributes
     *
     * @return string
     */
    public static function error($name, $attributes = null);

    /**
     * Generate help block.
     *
     * @param  string $text
     * @param  array $attributes
     *
     * @return string
     */
    public static function help($text, $attributes = null);

    /**
     * Convert array of attributes to HTML attribute string.
     *
     * @param  array $attributes
     *
     * @return string
     */
    public static function ats($attributes);

}
	
