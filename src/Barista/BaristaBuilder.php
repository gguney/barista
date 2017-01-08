<?php 
namespace Barista;

use ModelCourier\ColumnHelper\Column;

class BaristaBuilder{

	protected static $RESERVED_METHODS = ['POST', 'PUT', 'DELETE'];
	public static function open($array)
	{
		$method = strtoupper($array['method']);
		$method = (in_array($method ,self::$RESERVED_METHODS))?$method :'POST';
		$action = $array['url'];
		return '<form method="POST" action="'.$action.'">'.csrf_field().method_field($method);

	}
	public static function close()
	{
		return '<button type="submit" class="btn btn-primary">Submit</button></form>';
	}
	public static function buildFromDM($dataModel, $errors)
	{
		$htmlFields = "";
		//dd($errors);
		$columns = $dataModel->getColumns();
		$formFields = $dataModel->getFormFields();
		foreach ($formFields as $formField) {
			if(isset( $columns[$formField]))
				$column = $columns[$formField];
			else
			{
				
				$column = new Column();
				$column->setName($formField);
				$column->setId($formField);
				$column->setLabel( ucwords($formField) );
				$column->setRequired(true);
				$column->setEditable(true);
				$column->setType('text');
				
			}
			$htmlFields .= '<div class="form-group">';
			$htmlFields .='<label class="control-label" for="'.$column->getName().'">';

			$htmlFields .= $column->getLabel();
			$htmlFields .= ($column->getRequired())?'  <strong class="text-danger">*</strong>':'';
			$htmlFields .= '</label>';

			if($column->getMaxLength() > 255)
				$htmlFields .= '<textarea ';
			else
			{
				$htmlFields .= '<input ';
				$htmlFields .= 'type="'.$column->getType().'" ';
			}
			$htmlFields .='class="form-control" name="'.$column->getName().'" id="'.$column->getName().'" ';
			$htmlFields .='placeholder="Enter '.$column->getLabel().'" ';
			if(!$column->getEditable())
				$htmlFields .= 'disabled="disabled" ';
			if($column->getMaxLength() > 255)
				$htmlFields .='</textarea>';
			else
				$htmlFields .=' />';

			$htmlFields .= '</div>';
		}
		return $htmlFields;
	}
	public static function input($type)
	{

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

