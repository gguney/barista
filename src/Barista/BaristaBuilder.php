<?php 
namespace Barista;

use ModelCourier\ColumnHelper\Column;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Storage;

/*
HTTP Verb	Path (URL)			Action (Method)		Route Name
GET			/nerds				index				nerds.index
GET			/nerds/create		create				nerds.create
POST		/nerds				store				nerds.store
GET			/nerds/{id}			show				nerds.show
GET			/nerds/{id}/edit	edit				nerds.edit
PUT/PATCH	/nerds/{id}			update				nerds.update
DELETE		/nerds/{id}			destroy				nerds.destroy
*/
class BaristaBuilder{

	protected static $RESERVED_METHODS = ['POST', 'PUT','PATCH', 'DELETE'];
	protected static $RESERVED_ATTRIBUTES = [];
	
	public static function open($array )
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

	public static function close($attributes)
	{
		return '<button type="submit" class="'.$attributes['class'].'">'.$attributes['title'].'</button></form>';
	}
	public static function buildFromDM($dataModel, $item = null, $errors)
	{
		$htmlFields = "";
		$columns = $dataModel->getColumns();
		$formFields = $dataModel->getFormFields();

		foreach ($formFields as $formField) {
			if(isset( $columns[$formField]))
				$column = $columns[$formField];

			$attributes = $column->getAttributes();

			$error = ($errors->has($formField))?'has-error':'';
			$htmlFields .= '<div class="form-group '.$error.'">';
			$htmlFields .= self::label($attributes['name'],$attributes['label']);

			$htmlFields .= (isset($attributes['required']))?self::required($attributes['required']):null;
			$htmlFields .= self::detectInput($dataModel, $attributes, $formField, $item);
        	if ($errors->has($formField))
				$htmlFields .=self::error($errors->first($formField), ['class'=>'help-block']);
			$htmlFields .= '</div>';
		}
		return $htmlFields;
	}
	public static function detectInput($dataModel, $attributes, $formField, $item)
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
		{
			$input .= self::file($name , $value, $attributes);
		}
		else if($attributes['maxlength'] > 255)
			$input .= self::textarea($name , $value, $attributes);
		else if($attributes['type'] == 'checkbox')
			$input .= self::checkbox($name , $value, $attributes);
		else
			$input .= self::input($name , $value, $attributes);
		
		return $input;

	}
	public static function buildShowFromDM($dataModel, $item)
	{
		$htmlFields = "";
		$columns = $dataModel->getColumns();
		$hiddenFields = $dataModel->getHiddenFields();
		foreach($columns as $key => $column)
		{
	
			if(!in_array($column->get('name'), $hiddenFields ))
			{
				$value = ($item->$key != null)?$item->$key : '-';
				$text = (\Lang::has('general.'.$column->get('label')))?trans('general.'.$column->get('label')):$column->get('label');

				$htmlFields .= '<div class="col-md-4"><strong>'.$text.'</strong> </div>';
				$htmlFields .= '<div class="col-md-8">';
				if( $column->get('type') == 'file' )
				{
					$file = (str_contains($value, ['http', 'https']))?$value:Storage::url($value);
					$htmlFields .= '<img src="'.$file.'" style="width:300px;height:auto"/img>';
				}
				else
					$htmlFields .= e($value);
				$htmlFields .= '</div>';
			}
		}
		return $htmlFields;
	}
	public static function buildTableFromDM($dataModel, $items)
	{
		$htmlFields = "";
		$columns = $dataModel->getColumns();
		$tableFields = $dataModel->getTableFields();

		$prefixWithSlash = ( config('app.admin_prefix') !==null && config('app.admin_prefix') != '')?'/'.config('app.admin_prefix').'/':'';
		$prefix = ( config('app.admin_prefix') !==null && config('app.admin_prefix') != '')?config('app.admin_prefix'):'';
		$htmlFields .='<table class="table compact" id="myTable">';
		$htmlFields .='<thead class="thead-inverse">';

		$editText = (\Lang::has('general.Edit'))?trans('general.Edit'):'Edit';
		$deleteText = (\Lang::has('general.Delete'))?trans('general.Delete'):'Delete';
		$createText = (\Lang::has('general.Create'))?trans('general.Create'):'Create';
		$updateText = (\Lang::has('general.Update'))?trans('general.Update'):'Update';
		$showText = (\Lang::has('general.Show'))?trans('general.Show'):'Show';
		$actionsText = (\Lang::has('general.Actions'))?trans('general.Actions'):'Actions';


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

			$htmlFields .= '<tr >';
			foreach ($tableFields as $tableField)
			{
				$columnName = $columns[$tableField]->get('name');
				$value = $item->$columnName;
				$htmlFields .= '<td>'.e($value).'</td>';

			}
			$htmlFields .= '<td class="td w-clearfix" >';
			$htmlFields .= '<a class="btn btn-primary btn-sm pull-left" style="margin-right:6px" href="'.$prefixWithSlash.lcfirst($dataModel->getName()).'/'.$item->id.'/edit">'.$editText.'</a>';
			$htmlFields .= '<a class="btn btn-success btn-sm pull-left" style="margin-right:6px" href="'.$prefixWithSlash.lcfirst($dataModel->getName()).'/'.$item->id.'">'.$showText.'</a>';
			$htmlFields .= self::open([ 'method'=>'DELETE', 'item'=>$item, 'url'=>$prefix.'/'.lcfirst($dataModel->getName()) ]);
			$htmlFields .= self::close(['class'=>'btn btn-danger btn-sm"', 'title'=>$deleteText]);
			$htmlFields .= '</tr>';
		}	
		$htmlFields .= '</tbody>';

		$htmlFields .= '</table>';

		return $htmlFields;
	}
	public static function checkbox ($name, $value, $attributes = null)
	{
		if(!isset($attributes['class']))
			$attributes['class'] = 'checkbox';
		$checked = ($value == 1)?'checked="checked"':'';
		$input = '<input'.self::ats($attributes).' value="'.e($value).'" '.$checked.'/>';
		return $input;
	}
	public static function file ($name, $value, $attributes = null)
	{
		$file = "";
		$file .= self::input($name , $value, $attributes);
		$attributes['name'] = $name.'_url';
		$attributes['type'] = 'text';
		$file .= self::input($name , $value, $attributes);
		return $file;

	}
	public static function input ($name, $value, $attributes = null)
	{
		if(!isset($attributes['class']))
			$attributes['class'] = 'form-control';
		$input = '<input'.self::ats($attributes).' value="'.e($value).'"/>';
		return $input;
	}

	public static function textarea($name, $value, $attributes = null)
	{
		if(!isset($attributes['class']))
			$attributes['class'] = 'form-control';
		return '<textarea'.self::ats($attributes).'>'.e($value).'</textarea>';
	}

	public static function select($name, $value , $options ,$attributes = null)
	{	
		$select = "";
		$select .= '<select class="form-control" name="'.$attributes['name'].'">';
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
	public static function option($name, $value, $selected)
	{
		return '<option value="'.e($value).'"'.$selected.'>'. $name.'</option>';

	}
	public static function link($url, $value, $attributes)
	{
		return '<a href="'.$url.'"'.self::ats($attributes).'>'.e($value).'</a>';
	}
	public static function label($name, $value ,$attributes = null)
	{
		$value = (\Lang::has('general.'.e($value)))?trans('general.'.e($value)):e($value);
		return '<label class="control-label" for="'.$name.'"'.self::ats($attributes).'>'.$value.'</label>';
	}
	public static function required ($required = null)
	{
		return ($required == 'required')?' <strong class="text-danger">*</strong>':'';
	}
	public static function error($error, $attributes = null)
	{
		return '<span'.self::ats($attributes).'>'.$error.'</span>';
	}	
	public static function ats($attributes)
	{
		$string = "";
		if(!isset($attributes))
			return $string;
		foreach($attributes as $key=>$value )
		{
			$string .= self::toAttribute($key, $value);
		}
		return $string;
	}
	private static function toAttribute($key, $value)
	{
		return ' '.$key.'="'.e($value).'"'; 
	}
	/*
	  "id" => {#176 ▼
    +"showName": "Id"
    +"name": "id"
    +"fieldType": "number"
    +"editable": false
    +"columnDefault": null
    +"required": "NO"
    */

  }

?>

