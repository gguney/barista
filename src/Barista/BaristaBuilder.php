<?php 
namespace Barista;

use Barista\Contracts\BaristaBuilderContract;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Storage;

class BaristaBuilder implements BaristaBuilderContract{

    /**
     * Reserved methods for form.
     *
     * @var array
     */
	protected static $RESERVED_METHODS = ['POST', 'PUT','PATCH', 'DELETE'];

	/**
     * @var array
     */
	protected static $RESERVED_ATTRIBUTES = [];

    /**
     * Open a a new form.
     *
     * @param  array  $array
     * 
     * @return string
     */
	public static function open($array)
	{
		$method = strtoupper($array['method']);
		$method = (in_array($method ,self::$RESERVED_METHODS))?$method :'POST';
		$files = (isset($array['files']) && $array['files'] == true)? 'enctype="multipart/form-data"':'';

		switch($method){
			case 'PUT':
				$action = $array['url'].'/'.$array['item']->id;
				break;
			case 'DELETE':
				$action = $array['url'].'/'.$array['item']->id;
				break;
			default:
				$action = $array['url'];
				break;
		}
		return '<form method="POST" action="'.$action.'" '.$files.'>'.csrf_field().method_field($method);
	}

	/**
	 * Close a form.
	 *
	 * @param $attributes
	 * 
	 * @return string
	 */
	public static function close($attributes)
	{
		if(!isset($attributes['class']))
			$attributes['class'] = config('barista.btn_class').' '.config('barista.btn_primary').' '.config('barista.btn_additional_class');
		$cancelButtonClass = config('barista.btn_class').' '.config('barista.btn_cancel').' '.config('barista.btn_additional_class');

		if(config('barista.should_group_form'))
			return '<div class="'.config('barista.group_class').'"><button type="submit" class="'.$attributes['class'].'">'.$attributes['title'].'</button><button class="'.$cancelButtonClass.'" onclick="history.go(-1);">Cancel</button></form></div>';
		else 
			return '<button type="submit" class="'.$attributes['class'].'">'.$attributes['title'].'</button><button class="'.$cancelButtonClass.'" onclick="history.go(-1);">Cancel</button></form>';
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
			if(!in_array($formField, $hiddenFields ))
			{
				if(isset( $columns[$formField]))
					$column = $columns[$formField];
				$attributes = $column->getAttributes();
				$error = ($errors->has($formField)) ? ' '.config('barista.div_error_class') : '';
				$htmlFields .= '<div class="'.config('barista.group_class').$error.'">';
				$htmlFields .= '<div class="'.config('barista.group_label_class').'">';
				$htmlFields .= self::label($attributes['name'], $attributes['label'], $attributes);
				$htmlFields .= '</div>';
				$htmlFields .= '<div class="'.config('barista.group_input_class').'">';
				$htmlFields .= self::detectInput($dataModel, $attributes, $item);
	        	if ($errors->has($formField))
					$htmlFields .= self::error($errors->first($formField));
				$htmlFields .= '</div>';
				$htmlFields .= '</div>';
			}			
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
		$value = (isset($item))?$item->$name:old($name);
		if(isset($foreigns[$attributes['name']] ) && $foreignsData[$attributes['name']] )
			$input .= self::select($name, $value, $foreignsData[$attributes['name']], $attributes);
		else if($attributes['type'] == 'file')
			$input .= self::file($name , $value, $attributes);
		else if($attributes['maxlength'] > 255)
			$input .= self::textarea($name , $value, $attributes);
		else if($attributes['type'] == 'checkbox')
			$input .= self::checkbox($name , $value, $attributes);
		else
			$input .= self::input($name , $value, $attributes);
		
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
		
		$cancelButtonClass = config('barista.btn_class').' '.config('barista.btn_cancel').' '.config('barista.btn_additional_class');

		foreach($columns as $key => $column)
		{
			if(!in_array($column->get('name'), $hiddenFields ))
			{
				$text = (\Lang::has('general.'.$column->get('label')))?trans('general.'.$column->get('label')):$column->get('label');
				$htmlFields .= '<div class="'.$groupClass.'">';
				$htmlFields .= '<div class="'.$groupLabelClass.'">';
				$htmlFields .= self::label($text,$text);
				$htmlFields .= '</div>';
				$htmlFields .= '<div class="'.$groupInputClass.'">';
				$value = ($item->$key != null)?$item->$key : '-';
				if( $column->get('type') == 'file' )
				{
					$file = (str_contains($value, ['http', 'https']))?$value:Storage::url($value);
					$htmlFields .= '<img src="'.$file.'" style="width:200px;height:auto"/img>';
				}
				else
					$htmlFields .= '<span class="'.$valueClass.'">'.e($value).'</span>';
				$htmlFields .= '</div>';
				$htmlFields .= '</div>';
			}
		}
		$htmlFields .='<div class="'.config('barista.group_class').'"><button class="'.$cancelButtonClass.'" onclick="history.go(-1);">Cancel</button></div>';

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

		$prefixWithSlash = ( config('barista.prefix') !== null && config('barista.prefix') != '')?'/'.config('barista.prefix').'/':'';
		$prefix = ( config('barista.prefix') !== null && config('barista.prefix') != '')?config('barista.prefix'):'';
		$htmlFields .='<table class="'.config('barista.table_class').'" id="myTable">';
		$htmlFields .='<thead class="'.config('barista.thead_class').'">';

		$editText = (\Lang::has('general.Edit'))?trans('general.Edit'):'Edit';
		$deleteText = (\Lang::has('general.Delete'))?trans('general.Delete'):'Delete';
		$createText = (\Lang::has('general.Create'))?trans('general.Create'):'Create';
		$updateText = (\Lang::has('general.Update'))?trans('general.Update'):'Update';
		$showText = (\Lang::has('general.Show'))?trans('general.Show'):'Show';
		$actionsText = (\Lang::has('general.Actions'))?trans('general.Actions'):'Actions';

		$editButtonClasses = config('barista.tbl_btn_class').' '.config('barista.tbl_btn_primary').' '.config('barista.tbl_btn_sm_class').' '.config('barista.tbl_btn_additional_class');
		$showButtonClasses = config('barista.tbl_btn_class').' '.config('barista.tbl_btn_info').' '.config('barista.tbl_btn_sm_class').' '.config('barista.tbl_btn_additional_class');
		$deleteButtonClasses = config('barista.tbl_btn_class').' '.config('barista.tbl_btn_danger').' '.config('barista.tbl_btn_sm_class').' '.config('barista.tbl_btn_additional_class');

		$columns = $dataModel->getColumns();
		$tableFields = $dataModel->getTableFields();

		foreach($tableFields as $tableField)
		{
			$columnName = $columns[$tableField]->get('name');
			$title = $columns[ $tableField ]->get('label');
			$value = (\Lang::has('general.'.e($title)))?trans('general.'.e($title)):e($title);
			$htmlFields .= '<th>'.$value.'</th>';
		}
		$htmlFields .= '<th>'.$actionsText.'</th>';
		$htmlFields .= '</thead>';
		$htmlFields .= '<tbody>';

		foreach($items as $item)
		{

			$htmlFields .= '<tr>';
			foreach ($tableFields as $tableField)
			{
				$columnName = $columns[$tableField]->get('name');
				$value = $item->$columnName;
				$htmlFields .= '<td>'.e($value).'</td>';
			}
			$htmlFields .= '<td class="td w-clearfix" >';
			$htmlFields .= '<a class="'.$editButtonClasses.' pull-left" href="'.$prefixWithSlash.lcfirst($dataModel->getName()).'/'.$item->id.'/edit">'.$editText.'</a>';
			$htmlFields .= '<a class="'.$showButtonClasses.' pull-left" href="'.$prefixWithSlash.lcfirst($dataModel->getName()).'/'.$item->id.'">'.$showText.'</a>';
			$htmlFields .= self::open([ 'method'=>'DELETE', 'item'=>$item, 'url'=>$prefix.'/'.lcfirst($dataModel->getName()) ]);
			$htmlFields .= '<button type="submit" class="'.$deleteButtonClasses.'">'.$deleteText.'</button></form>';
			$htmlFields .= '</tr>';
		}	
		$htmlFields .= '</tbody>';
		$htmlFields .= '</table>';

		return $htmlFields;
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
		if(!isset($attributes['class']))
			$attributes['class'] = config('barista.checkbox_class');
		$checked = ($value == 1)?'checked="checked"':'';
		$input = '<label class="'.config('barista.checkbox_class').'"><input'.self::ats($attributes).' value="'.e($value).'" '.$checked.'/>'.config('barista.checkbox_class').'</label>';
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
		$file .= self::input($name , $value, $attributes);
		$attributes['name'] = $name.'_url';
		$attributes['type'] = 'text';
		$file .= self::input($name , $value, $attributes);
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
		if(!isset($attributes['class']))
			$attributes['class'] = config('barista.input_class');
		$input = '<input'.self::ats($attributes).' value="'.e($value).'"/>';
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
		if(!isset($attributes['class']))
			$attributes['class'] = config('barista.textarea_class');
		return '<textarea'.self::ats($attributes).'>'.e($value).'</textarea>';
	}

	/**
	 * Generate an HTML select element with given options.
	 * 
	 * @param  string $name      
	 * @param  string $value    
	 * @param  array $options  
	 * @param  array $attributes 
	 * 
	 * @return string
	 */
	public static function select($name, $value , $options ,$attributes = null)
	{	
		$select = "";
		if(!isset($attributes['class']))
			$attributes['class'] = config('barista.select_class');
		$select .= '<select '.self::ats($attributes).'>';
		foreach($options as $option)
		{	
			if(isset($value) && $value == $option->id)
				$select .= self::option($option->name, $option->id, 'selected');
			else
				$select .= self::option($option->name, $option->id, '');
		} 
		$select .='</select>';
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
		return '<option value="'.e($value).'"'.$selected.'>'. $name.'</option>';
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
		if(!isset($attributes['class']))
			$attributes['class'] = config('barista.link_class');
		return '<a href="'.$url.'"'.self::ats($attributes).'>'.e($value).'</a>';
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
	public static function label($name, $value ,$attributes = null)
	{
		if(!isset($attributes['class']))
			$attributes['class'] = config('barista.label_class');
		$value = (\Lang::has('general.'.e($value)))?trans('general.'.e($value)):e($value);
		$required = (isset($attributes['required']) && $attributes['required'] == "required" ) ? self::required():'';
		return '<label for="'.$name.'"'.self::ats($attributes).'>'.$value.' '.$required.'</label>';
	}

	/**
	 * Generate required tag.
	 * 
	 * @return string
	 */
	public static function required ()
	{
		return '<strong class="'.config('barista.required_block_class').'">'.config('barista.required_tag').'</strong>';
	}

	/**
	 * Generate error block.
	 * 
	 * @param  string $error  
	 * @param  array $attributes
	 * 
	 * @return string       
	 */
	public static function error($error, $attributes = null)
	{
		if(!isset($attributes['class']))
			$attributes['class'] = config('barista.error_block_class');
		return '<span'.self::ats($attributes).'>'.$error.'</span>';
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
		if(!isset($attributes['class']))
			$attributes['class'] = config('barista.help_block_class');
		return '<span'.self::ats($attributes).'>'.$text.'</span>';
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
		if(!isset($attributes))
			return $string;
		foreach($attributes as $key=>$value )
		{
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
		return ' '.$key.'="'.e($value).'"'; 
	}

  }

?>

