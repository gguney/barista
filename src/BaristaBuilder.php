<?php
namespace GGuney\Barista;

use GGuney\Barista\Contracts\BaristaBuilderContract;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Storage;

class BaristaBuilder implements BaristaBuilderContract
{

    /**
     * Reserved methods for form.
     *
     * @var array
     */
    protected static $RESERVED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * @var array
     */
    protected static $RESERVED_ATTRIBUTES = [];

    /**
     * Open a a new form.
     *
     * @param  array $array
     * @param  array $attributes
     *
     * @return string
     */
    public static function open(array $array, array $attributes = null): string
    {
        $method = strtoupper($array['method']);
        $method = (in_array($method, self::$RESERVED_METHODS)) ? $method : 'POST';
        $files = (isset($array['files']) && $array['files'] == true) ? 'enctype="multipart/form-data"' : '';
        $action = isset($array['url'])?$array['url']: null;
        if(!isset($action)){
            switch ($method) {
                case 'PUT':
                    $action = $array['url'] . '/' . $array['item']->id;
                    break;
                case 'DELETE':
                    $action = $array['url'] . '/' . $array['item']->id;
                    break;
                default:
                    $action = $array['url'];
                    break;
            }
        }

        return '<form method="POST" action="' . $action . '" ' . $files . self::ats($attributes) . '>' . csrf_field() . method_field($method);
    }

    /**
     * Close a form.
     *
     * @param $attributes
     *
     * @return string
     */
    public static function close(array $attributes): string
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.btn_class') . ' ' . config('barista.btn_primary') . ' ' . config('barista.btn_additional_class');
        }
        $cancelButtonClass = config('barista.btn_class') . ' ' . config('barista.btn_cancel') . ' ' . config('barista.btn_additional_class');

        if (config('barista.should_group_form')) {
            return '<div class="' . config('barista.group_class') . '"><button type="submit" class="' . $attributes['class'] . '">' . $attributes['title'] . '</button><button class="' . $cancelButtonClass . '" onclick="history.go(-1);">Cancel</button></form></div>';
        } else {
            return '<button type="submit" class="' . $attributes['class'] . '">' . $attributes['title'] . '</button><button class="' . $cancelButtonClass . '" onclick="history.go(-1);">Cancel</button></form>';
        }
    }

    /**
     * Build Form From a DataModel
     *
     * @param  DataModel $dataModel
     * @param  Model $item
     * @param  array $errors
     *
     * @return string
     */
    public static function buildFromDM($dataModel, $item = null, $errors)
    {
        $htmlFields = "";
        $columns = $dataModel->getColumns();
        $formFields = $dataModel->getFormFields();
        $hiddenFields = $dataModel->getHiddenFields();
        foreach ($formFields as $formField) {
            if (isset($columns[$formField])) {
                $column = $columns[$formField];
            }
            $attributes = $column->getAttributes();
            $error = ($errors->has($formField)) ? ' ' . config('barista.div_error_class') : '';
            $htmlFields .= '<div class="' . config('barista.group_class') . $error . '">';
            $htmlFields .= '<div class="' . config('barista.group_label_class') . '">';
            $htmlFields .= self::label($attributes['name'], $attributes['label'], $attributes);
            $htmlFields .= '</div>';
            $htmlFields .= '<div class="' . config('barista.group_input_class') . '">';
            $htmlFields .= self::detectInput($dataModel, $attributes, $item);
            if ($errors->has($formField)) {
                $htmlFields .= self::error($errors->first($formField));
            }
            $htmlFields .= '</div>';
            $htmlFields .= '</div>';
        }

        return $htmlFields;
    }

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
    public static function detectInput($dataModel, $attributes, $item)
    {
        $foreigns = $dataModel->getForeigns();
        $foreignsData = $dataModel->getForeignsData();
        $columns = $dataModel->getColumns();
        $input = "";
        $name = $attributes['name'];
        $value = (isset($item)) ? $item->$name : old($name);
        if (isset($foreigns[$attributes['name']]) && $foreignsData[$attributes['name']]) {
            $input .= self::select($name, $value, $attributes, $foreignsData[$attributes['name']]);
        } else {
            if ($attributes['type'] == 'file') {
                $input .= self::file($name, $value, $attributes);
            } else {
                if ($attributes['maxlength'] > 255) {
                    $input .= self::textarea($name, $value, $attributes);
                } else {
                    if ($attributes['type'] == 'checkbox') {
                        $input .= self::checkbox($name, $value, $attributes);
                    } else {
                        $input .= self::input($name, $value, $attributes);
                    }
                }
            }
        }

        return $input;
    }

    /**
     * Build show partial from DataModel
     *
     * @param  DataModel $dataModel
     * @param  Model $item
     *
     * @return string
     */
    public static function buildShowFromDM($dataModel, $item)
    {
        $htmlFields = "";
        $columns = $dataModel->getColumns();
        $hiddenFields = $dataModel->getHiddenFields();

        $groupClass = config('barista.show_group_class');
        $groupLabelClass = config('barista.show_group_label_class');
        $groupInputClass = config('barista.show_group_value_class');
        $valueClass = config('barista.show_value_class');
        $valueTag = config('barista.show_value_tag');
        $cancelButtonClass = config('barista.btn_class') . ' ' . config('barista.btn_cancel') . ' ' . config('barista.btn_additional_class');

        foreach ($columns as $key => $column) {
            if (!in_array($column->get('name'), $hiddenFields)) {
                $text = (\Lang::has('general.' . $column->get('label'))) ? trans('general.' . $column->get('label')) : $column->get('label');
                $htmlFields .= '<div class="' . $groupClass . '">';
                $htmlFields .= '<div class="' . $groupLabelClass . '">';
                $htmlFields .= self::label($text, $text);
                $htmlFields .= '</div>';
                $htmlFields .= '<div class="' . $groupInputClass . '">';
                $value = ($item->$key != null) ? $item->$key : '-';
                if ($column->get('type') == 'file') {
                    $fileSystems = config('filesystems.default');
                    if (str_contains($value, ['http', 'https']) && $fileSystems == 'local') {
                        $file = Storage::url($value);
                    } else {
                        if ($fileSystems == 's3') {
                            $file = Storage::disk('s3')->url($value);
                        } else {
                            $file = $value;
                        }
                    }
                    $htmlFields .= '<img src="' . $file . '" style="width:200px;height:auto"/img>';
                } else {
                    $htmlFields .= '<' . $valueTag . ' class="' . $valueClass . '">' . $value . '</' . $valueTag . '>';
                }
                $htmlFields .= '</div>';
                $htmlFields .= '</div>';
            }
        }
        $htmlFields .= '<div class="' . config('barista.group_class') . '"><button class="' . $cancelButtonClass . '" onclick="history.go(-1);">Cancel</button></div>';

        return $htmlFields;
    }

    /**
     * Build table partial from DataModel
     *
     * @param  DataModel $dataModel
     * @param  Model $items
     *
     * @return string
     */
    public static function buildTableFromDM($dataModel, $items)
    {
        $htmlFields = "";

        $prefixWithSlash = (config('barista.prefix') !== null && config('barista.prefix') != '') ? '/' . config('barista.prefix') . '/' : '';
        $prefix = (config('barista.prefix') !== null && config('barista.prefix') != '') ? config('barista.prefix') : '';
        $htmlFields .= '<table class="' . config('barista.table_class') . '" id="myTable">';
        $htmlFields .= '<thead class="' . config('barista.thead_class') . '">';

        $editText = (\Lang::has('general.Edit')) ? trans('general.Edit') : 'Edit';
        $deleteText = (\Lang::has('general.Delete')) ? trans('general.Delete') : 'Delete';
        $createText = (\Lang::has('general.Create')) ? trans('general.Create') : 'Create';
        $updateText = (\Lang::has('general.Update')) ? trans('general.Update') : 'Update';
        $showText = (\Lang::has('general.Show')) ? trans('general.Show') : 'Show';
        $actionsText = (\Lang::has('general.Actions')) ? trans('general.Actions') : 'Actions';

        $editButtonClasses = config('barista.tbl_btn_class') . ' ' . config('barista.tbl_btn_primary') . ' ' . config('barista.tbl_btn_sm_class') . ' ' . config('barista.tbl_btn_additional_class') . ' ' . config('barista.tbl_btn_additional_class');
        $showButtonClasses = config('barista.tbl_btn_class') . ' ' . config('barista.tbl_btn_info') . ' ' . config('barista.tbl_btn_sm_class') . ' ' . config('barista.tbl_btn_additional_class');
        $deleteButtonClasses = config('barista.tbl_btn_class') . ' ' . config('barista.tbl_btn_danger') . ' ' . config('barista.tbl_btn_sm_class') . ' ' . config('barista.tbl_btn_additional_class');

        $columns = $dataModel->getColumns();
        $tableFields = $dataModel->getTableFields();

        foreach ($tableFields as $tableField) {
            $title = $columns[$tableField]->get('label');
            $value = (\Lang::has('general.' . e($title))) ? trans('general.' . e($title)) : e($title);
            $htmlFields .= '<th>' . $value . '</th>';
        }
        $htmlFields .= '<th>' . $actionsText . '</th>';
        $htmlFields .= '</thead>';
        $htmlFields .= '<tbody>';

        foreach ($items as $item) {

            $htmlFields .= '<tr>';
            foreach ($tableFields as $tableField) {
                $columnName = $columns[$tableField]->get('name');
                $value = $item->$columnName;
                $htmlFields .= '<td>' . e($value) . '</td>';
            }
            $htmlFields .= '<td class="td w-clearfix" >';
            $htmlFields .= '<a class="' . $editButtonClasses . ' pull-left" href="' . $prefixWithSlash . lcfirst($dataModel->getName()) . '/' . $item->id . '/edit">' . $editText . '</a>';
            $htmlFields .= '<a class="' . $showButtonClasses . ' pull-left" href="' . $prefixWithSlash . lcfirst($dataModel->getName()) . '/' . $item->id . '">' . $showText . '</a>';
            $htmlFields .= self::open([
                'method' => 'DELETE',
                'item'   => $item,
                'url'    => $prefix . '/' . lcfirst($dataModel->getName())
            ]);
            $htmlFields .= '<button type="submit" class="' . $deleteButtonClasses . '">' . $deleteText . '</button></form>';
            $htmlFields .= '</tr>';
        }
        $htmlFields .= '</tbody>';
        $htmlFields .= '</table>';

        return $htmlFields;
    }

    public static function showBar(string $name, string $value)
    {
        return '<dt>' . $name . '</dt>' . '<dd>' . $value . '</dd>';
    }

    public static function formBar(string $name, string $value, array $attributes = null)
    {
        $html = '';
        $html .= self::groupOpen($name, ['errors' => $attributes['errors']]);
        $html .= self::label($name, $value, $attributes);
        $attributes['value'] = (isset($attributes['value'])) ? $attributes['value'] : null;

        if($attributes['type'] != 'select'){
            $html .= self::input($name, $attributes['value'], $attributes, $attributes['errors']);
        }
        else{
            $html .= self::select($name, $attributes['value'], $attributes, $attributes['options']);
        }
        $html .= self::error($name, $attributes);
        $html .= self::groupClose();
        return $html;
    }

    /**
     * Generate group div.
     *
     * @param string $name
     * @param array $attributes
     * @return string
     */
    public static function groupOpen(string $name, array $attributes = null): string
    {
        isset($attributes['class']) ?: $attributes['class'] = config('barista.group_class');
        (isset($attributes['errors']) && $attributes['errors']->has($name)) ? $attributes['class'] .= ' ' . config('barista.error_block_class') : '';
        return '<div ' . self::ats($attributes) . '>';
    }

    /**
     * Close group div.
     *
     * @return string
     */
    public static function groupClose()
    {
        return '</div>';
    }

    /**
     * Generate an HTML checkbox element.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function checkbox($name, $value, $attributes = null)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.checkbox_class');
        }
        $checked = ($value == 1) ? 'checked="checked"' : '';
        $input = '<label class="' . config('barista.checkbox_class') . '"><input' . self::ats($attributes) . ' value="' . e($value) . '" ' . $checked . '/>' . e($value) . '</label>';

        return $input;
    }

    /**
     * Generate an HTML file input with path and url.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function file($name, $value, $attributes = null)
    {
        $file = "";
        $file .= self::input($name, $value, $attributes);
        $attributes['name'] = $name . '_url';
        $attributes['type'] = 'text';
        $file .= self::input($name, $value, $attributes);

        return $file;

    }

    /**
     * Generate an HTML input element
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function input($name, $value, $attributes = null)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.input_class');
        }
        $input = '<input name="' . $name .'" '. self::ats($attributes) . ((isset($value)) ? ' value="' . e($value) . '"' : '') . '/>';

        return $input;
    }

    /**
     * Generate an HTML textarea element.
     *
     * @param  string $name
     * @param  string $value
     * @param  string $attributes
     *
     * @return string
     */
    public static function textarea($name, $value, $attributes = null)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.textarea_class');
        }

        return '<textarea' . self::ats($attributes) . '>' . ((isset($value)) ? e($value) : null) . '</textarea>';
    }

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
    public static function select($name, $value, $attributes = null, $options)
    {
        $select = "";
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.select_class');
        }
        $select .= '<select name="'.$name.'" ' . self::ats($attributes) . '>';
        foreach ($options as $option) {
            if (isset($value) && $value == $option->id) {
                $select .= self::option($option->name, $option->id, 'selected');
            } else {
                $select .= self::option($option->name, $option->id, '');
            }
        }
        $select .= '</select>';

        return $select;

    }

    /**
     * Generate an option for an HTML select.
     *
     * @param  string $name
     * @param  string $value
     * @param  string $selected
     *
     * @return string
     */
    public static function option($name, $value, $selected)
    {
        return '<option value="' . e($value) . '"' . $selected . '>' . $name . '</option>';
    }

    /**
     * Generate an HTML link.
     *
     * @param  string $url
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function link($url, $value, $attributes)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.link_class');
        }

        return '<a href="' . $url . '"' . self::ats($attributes) . '>' . e($value) . '</a>';
    }

    /**
     * Generate an HTML label.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function label($name, $value, $attributes = null)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.label_class');
        }
        $value = (\Lang::has('general.' . e($value))) ? trans('general.' . e($value)) : e($value);
        $required = (isset($attributes['required']) && $attributes['required'] == "required") ? self::required() : '';

        return '<label for="' . $name . '" class="' . $attributes["class"] . '">' . $value . ' ' . $required . '</label>';
    }

    /**
     * Generate required tag.
     *
     * @return string
     */
    public static function required()
    {
        return '<strong class="' . config('barista.required_block_class') . '">' . config('barista.required_tag') . '</strong>';
    }

    /**
     * Generate error block.
     *
     * @param  string $name
     * @param  string $errors
     * @param  array $attributes
     *
     * @return string
     */
    public static function error($name, $attributes = null)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.error_text_class');
        }
        $errors = $attributes['errors'];
        if ($errors->has($name)) {
            return '<span' . self::ats($attributes) . '>' . $errors->first($name) . '</span>';
        }
        return '';
    }

    /**
     * Generate help block.
     *
     * @param  string $text
     * @param  array $attributes
     *
     * @return string
     */
    public static function help($text, $attributes = null)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.help_block_class');
        }

        return '<span' . self::ats($attributes) . '>' . $text . '</span>';
    }

    /**
     * Convert array of attributes to HTML attribute string.
     *
     * @param  array $attributes
     *
     * @return string
     */
    public static function ats($attributes)
    {
        $string = "";
        if (!isset($attributes)) {
            return $string;
        }
        foreach ($attributes as $key => $value) {
            $string .= self::toHTMLAttribute($key, $value);
        }

        return $string;
    }

    /**
     * Convert key, value pair to HTML Attribute string format.
     *
     * @param  string $key
     * @param  string $value
     *
     * @return string
     */
    private static function toHTMLAttribute($key, $value)
    {
        if (is_string($value)) {
            return ' ' . $key . '="' . e($value) . '"';
        }
        return '';
    }

}

?>

