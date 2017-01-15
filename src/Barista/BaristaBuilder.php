<?php 
namespace Barista;

use ModelCourier\ColumnHelper\Column;
use Illuminate\Session\Store as Session;

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

		switch($method){
			case 'PUT':
				$action = '/'.$array['url'].'/'.$array['item']->id;
				break;
			case 'DELETE':
				$action = $array['url'].'/'.$array['item']->id;
				break;
			default:
				$action ='/'.$array['url'];
				break;
		}

		return '<form method="POST" action="'.$action.'">'.csrf_field().method_field($method);

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
		if(isset($columns[$formField]->getSpecialType()))
		{
			$attributes[$name]->set('type',$columns[$name]->getSpecialType());
			$input .= self::input($name , $value, $attributes);
		}
		else if(isset($foreigns[$attributes['name']] ) && $foreignsData[$attributes['name']] )
			$input .= self::select($name, $value, $foreignsData[$attributes['name']], $attributes);
		else if($attributes['maxlength'] > 255)
			$input .= self::textarea($name , $value, $attributes);
		else if($attributes['maxlength'] == 1)
			$input .= self::checkbox($name , $value, $attributes);
		else
			$input .= self::input($name , $value, $attributes);
		
		return $input;

	}
	public static function buildShowFromDM($dataModel, $item)
	{
		$htmlFields = "";
		$columns = $dataModel->getColumns();
		foreach($columns as $key => $column)
		{
			$value = ($item->$key != null)?$item->$key : '-';
			$htmlFields .='<div class="col-md-4"><strong>'.$column->get('label').'</strong> </div><div class="col-md-8">'.e($value).'</div>';
		}
		return $htmlFields;
	}
	public static function buildTableFromDM($dataModel, $items)
	{
		$htmlFields = "";
		$columns = $dataModel->getColumns();
		$tableFields = $dataModel->getTableFields();
		$htmlFields .='<table class="table compact" id="myTable">';
		$htmlFields .='<thead class="thead-inverse">';
		foreach($tableFields as $tableField)
		{
			$columnName = $columns[$tableField]->get('name');
			$title = $columns[ $tableField ]->get('label');
			$htmlFields .= '<th>'.$title.'</th>';
		}
		$htmlFields .= '<th>Actions</th>';
		$htmlFields .= '</thead>';
		$htmlFields .= '<tbody>';

		foreach($items as $item)
		{

			$htmlFields .= '<tr >';
			foreach ($tableFields as $tableField)
			{
				$columnName = $columns[$tableField]->get('name');
				$value = $item->$columnName;
				$htmlFields .= '<td>'.$value.'</td>';

			}
			$htmlFields .= '<td class="td w-clearfix" >';
			$htmlFields .= '<a class="btn btn-primary btn-sm pull-left" style="margin-right:6px" href="/'.config('app.admin_prefix').'/'.lcfirst($dataModel->getName()).'/'.$item->id.'/edit">Edit</a>';
			$htmlFields .= '<a class="btn btn-success btn-sm pull-left" style="margin-right:6px" href="/'.config('app.admin_prefix').'/'.lcfirst($dataModel->getName()).'/'.$item->id.'">Show</a>';
			$htmlFields .= self::open([ 'method'=>'DELETE', 'item'=>$item, 'url'=>config('app.admin_prefix').'/'.lcfirst($dataModel->getName()) ]);
			$htmlFields .= self::close(['class'=>'btn btn-danger btn-sm"', 'title'=>'Delete']);
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
		$input = '<input'.self::attributesToString($attributes).' value="'.e($value).'"/>';
		return $input;
	}
	public static function input ($name, $value, $attributes = null)
	{
		if(!isset($attributes['class']))
			$attributes['class'] = 'form-control';
		$input = '<input'.self::attributesToString($attributes).' value="'.e($value).'"/>';
		return $input;
	}

	public static function textarea($name, $value, $attributes = null)
	{
		if(!isset($attributes['class']))
			$attributes['class'] = 'form-control';
		return '<textarea'.self::attributesToString($attributes).'>'.e($value).'</textarea>';
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
		return '<a href="'.$url.'"'.self::attributesToString($attributes).'>'.e($value).'</a>';
	}
	public static function label($name, $value ,$attributes = null)
	{
		return '<label class="control-label" for="'.$name.'"'.self::attributesToString($attributes).'>'.e($value).'</label>';
	}
	public static function required ($required = null)
	{
		return ($required == 'required')?' <strong class="text-danger">*</strong>':'';
	}
	public static function error($error, $attributes = null)
	{
		return '<span'.self::attributesToString($attributes).'>'.$error.'</span>';
	}	
	public static function attributesToString($attributes)
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

