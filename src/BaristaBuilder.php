<?php
namespace GGuney\Barista;

use GGuney\Barista\Contracts\BaristaBuilderContract;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;
use Psr\Log\InvalidArgumentException;

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
        $method = isset($array['method']) ? strtoupper($array['method']) : 'POST';
        $method = (in_array($method, self::$RESERVED_METHODS)) ? $method : 'POST';
        $files = (isset($array['files']) && $array['files'] == true) ? 'enctype="multipart/form-data"' : '';
        $action = isset($array['url']) ? $array['url'] : null;
        if (!isset($action)) {
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
    public static function close(): string
    {
        return '</form>';
    }

    /**
     * Close a form with button.
     *
     * @param $attributes
     *
     * @return string
     */
    public static function closeWithButton(array $attributes): string
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

    public static function showBar(string $name, $value): string
    {
        $row = '<dt>' . $name . '</dt>';
        if (!is_array($value)) {
            $row .= '<dd>' . $value . '</dd>';
        } else {
            $row .= '<dd>' . implode(', ', $value);
            $row .= '</dd>';
        }
        return $row;

    }

    public static function showTable(string $name, $value, array $attributes = null): string
    {
        $row = '<tr>';
        $row.= '<td style="border: 1px solid black;width:200px "><strong>' .$name. '</strong></td>' ;
        $row .= '<td style="border: 1px solid black">' ;
        if (!is_array($value)) {
            $row .= $value ;
        } else {
            $row .= implode(', ', $value);
        }
        $row .= '</td>';
        $row .= '</tr>';

        return $row;

    }

    public static function fieldset(string $name, string $label, array $attributes = null)
    {
        $html = '';
        $html .= '<fieldset>';
        if($attributes['type'] != 'checkbox')
        {
            $html .= self::label($name, $label, $attributes);
        }
        if ($attributes['errors']->has($name)) {
            $html .= '<div class="control has-danger">';
        }else{
            $html .= '<div class="control">';
        }
        $attributes['value'] = (isset($attributes['value'])) ? $attributes['value'] : null;
        switch ($attributes['type']) {
            case 'textarea':
                $html .= self::textarea($name, $attributes['value'], $attributes);
                break;
            case 'select':
                $html .= self::select($name, $attributes['value'], $attributes);
                break;
            case 'checkbox':
                $attributes['checked'] = ($attributes['value'] > 0) ? true: false;
                $html .= self::checkbox($name, $label, $attributes);
                break;
            case 'file':
                $html .= self::file($name, $attributes['value'], $attributes);
                break;
            default:
                $html .= self::input($name, $attributes['value'], $attributes, $attributes['errors']);
                break;
        }
        $html .= '</div>';
        $html .= self::fieldsetError($name, $attributes);
        $html .= '</fieldset>';
        return $html;
    }
    public static function formBar(string $name, string $label, array $attributes = null)
    {
        $html = '';
        $html .= self::groupOpen($name, ['errors' => $attributes['errors']]);
        $html .= self::label($name, $label, $attributes);
        $attributes['value'] = (isset($attributes['value'])) ? $attributes['value'] : null;
        switch ($attributes['type']) {
            case 'textarea':
                $html .= self::textarea($name, $attributes['value'], $attributes);
                break;
            case 'select':
                $html .= self::select($name, $attributes['value'], $attributes);
                break;
            case 'checkbox':
                $attributes['checked'] = ($attributes['value'] > 0) ? true: false;
                $html .= self::checkbox($name, $label, $attributes);
                break;
            case 'file':
                $html .= self::file($name, $attributes['value'], $attributes);
                break;
            default:
                $html .= self::input($name, $attributes['value'], $attributes, $attributes['errors']);
                break;
        }
        $html .= self::error($name,$attributes['errors'], $attributes);
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
        $checked = (isset($attributes['checked']) && ($attributes['checked'] == 1 || $attributes['checked'] == true )) ? 'checked="checked"' : '';
        unset($attributes['value']);
        unset($attributes['checked']);
        $label = isset( $attributes['label']) ?  $attributes['label'] : '';
        $input = '<label class="' . config('barista.checkbox_class') . '"><input style="margin-right:0.25rem"  type="checkbox" name="' . $name . '" ' . self::ats($attributes)  . $checked . '/>' .$label.'</label>';
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
     * Generate an HTML password input lement
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function password($name, $value = null, $attributes = null)
    {
        $attributes['type'] = 'password';
        return self::input($name, $value, $attributes);
    }

    /**
     * Generate an HTML email input lement
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function email($name, $value = null, $attributes = null)
    {
        $attributes['type'] = 'email';
        return self::input($name, $value, $attributes);
    }

    /**
     * Generate an HTML text input lement
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function text($name, $value = null, $attributes = null)
    {
        $attributes['type'] = 'text';
        return self::input($name, $value, $attributes);
    }

    /**
     * Generate an HTML number input lement
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function number($name, $value = null, $attributes = null)
    {
        $attributes['type'] = 'number';
        return self::input($name, $value, $attributes);
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
    public static function input($name, $value = null, $attributes = null)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.input_class');
        }
        $input = '<input '. self::ats($attributes) . ' name="' . $name . '" '. ((isset($value)) ? ' value="' . e($value) . '"' : '') . '/>';

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
        $attributes['rows'] = $attributes['rows'] ?? 4;
        $attributes['cols'] = $attributes['cols'] ?? 100;
        return '<textarea name="'.$name.'"' . self::ats($attributes) . '>' . ((isset($value)) ? e($value) : null) . '</textarea>';
    }

    /**
     * Generate an HTML select element with given options.
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function select($name, $value, $attributes = null)
    {
        $select = "";
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.select_class');
        }
        $select .= '<select name="' . $name . '" ' . self::ats($attributes) . '>';
        $options = $attributes['options'];
        $checkedOptions = [];
        if( isset( $value) ){
            (is_array($value)) ? $checkedOptions = $value : $checkedOptions[] =  $value;
        }
        if(!$options){
            $error = $name. ' Not Found';
            new Exception($error);
        }
        foreach ($options as $option) {
            if ( in_array($option['id'], $checkedOptions))
            {
                $select .= self::option($option['name'], $option['id'], 'selected');
            } else {
                $select .= self::option($option['name'], $option['id'], '');
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
    public static function link($url, $value, $attributeas)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.link_class');
        }

        return '<a href="' . $url . '"' . self::ats($attributes) . '>' . e($value) . '</a>';
    }

    /**
     * Generate an HTML hidden element
     *
     * @param  string $name
     * @param  string $value
     * @param  array $attributes
     *
     * @return string
     */
    public static function hidden($name, $value, $attributes = null)
    {
        $input = '<input type="hidden" name="' . $name . '" ' . self::ats($attributes) . ((isset($value)) ? ' value="' . e($value) . '"' : '') . '/>';
        return $input;
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
    public static function fieldsetError($name, $attributes = null)
    {
        $errors = $attributes['errors'];
        if ($errors->has($name)) {
            return '<p class="help is-danger" >' . $errors->first($name) . '</span>';
        }
        return '';
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
    public static function error($name, $errors ,$attributes = null)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.error_text_class');
        }
        if ($errors->has($name)) {
            return '<p' . self::ats($attributes) . '>' . $errors->first($name) . '</p>';
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

        return '<p' . self::ats($attributes) . '>' . $text . '</span>';
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
     * Postable button.
     *
     * @param string $action
     * @param string $text
     * @param string $class
     *
     * @return string
     */
    public static function postButton($action, $text, $class)
    {
        $id = str_random(8);
        $form = '<form id="'.$id.'" action="'.$action.'" method="POST" style="display: none;">';
        $form .= csrf_field();
        $form .= '</form>';
        $form .= '<a href="#" class="'.$class.'"
                               onclick="document.getElementById(\''.$id.'\').submit()">
                                   '.$text.'
                            </a>';
        return $form;
    }
    /**
     * Postable button.
     *
     * @param string $name
     * @param string $value
     * @param string $attributes
     *
     * @return string
     */
    public static function submit($name, $value, $attributes = null)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = config('barista.submit_button_class');
        }
        $value = (\Lang::has('general.' . e($value))) ? trans('general.' . e($value)) : e($value);
        return '<button type="submit" class="button is-primary ' . $attributes["class"] . '">' . $value . '</button>';
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
        if (is_string($value) || is_int($value)) {
            return ' ' . $key . '="' . e($value) . '"';
        }
        return '';
    }

}

?>

