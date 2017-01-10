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
	public static function open($array )
	{
		$method = strtoupper($array['method']);
		$method = (in_array($method ,self::$RESERVED_METHODS))?$method :'POST';

		switch($method){
			case 'PUT':
				$action = '/'.$array['url'].'/'.$array['item']->id;
				break;
			case 'DELETE':
				$action = '/'.$array['url'].'/'.$array['item']->id;
				break;
			default:
				$action = '/'.$array['url'];
				break;
		}

		return '<form method="POST" action="'.$action.'">'.csrf_field().method_field($method);

	}

	public static function close()
	{
		return '<button type="submit" class="btn btn-primary">Submit</button></form>';
	}
	public static function buildFromDM($dataModel,$item = null, $errors)
	{
		$htmlFields = "";

		$columns = $dataModel->getColumns();
		$formFields = $dataModel->getFormFields();
		$foreigns = $dataModel->getForeigns();
		$foreignsData = $dataModel->getForeignsData();

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
			$error = ($errors->has($formField))?'has-error':'';
			$htmlFields .= '<div class="form-group '.$error.'">';
			$htmlFields .='<label class="control-label" for="'.$column->getName().'">';

			$htmlFields .= $column->getLabel();
			$htmlFields .= ($column->getRequired())?'  <strong class="text-danger">*</strong>':'';
			$htmlFields .= '</label>';
			if(isset($foreigns[$column->getName()] ) && $foreignsData[$column->getName()] )
			{
				$htmlFields .='<select class="form-control" name="'.$column->getName().'">';
				foreach($foreignsData[$column->getName()] as $foreignData)
				{	
					if(isset($item))
					{
						$columnName = $column->getName();
						if($item->$columnName == $foreignData->id)
							$htmlFields .='<option value="'.$foreignData->id.'" selected>'. $foreignData->name.'</option>';
						else
							$htmlFields .='<option value="'.$foreignData->id.'">'. $foreignData->name.'</option>';

					}
					else
					{
						$htmlFields .='<option value="'.$foreignData->id.'">'. $foreignData->name.'</option>';

					}
				} 
				$htmlFields .='<select>';
			}
			else
			{
				if($column->getMaxLength() > 255)
					$htmlFields .= '<textarea ';
				else
				{
					$htmlFields .= '<input ';
					$htmlFields .= 'type="'.$column->getType().'" ';
				}
				$htmlFields .='class="form-control" name="'.$column->getName().'" id="'.$column->getName().'" ';
				$htmlFields .='placeholder="Enter '.$column->getLabel().'" ';
				if(isset($item))
				{
					$colName = $column->getName();
					$value = $item->$colName;
					$htmlFields .='value="'.$value.'"';
				}
				else
					$htmlFields .='value="'.old($column->getName()).'"';

				if(!$column->getEditable())
					$htmlFields .= 'disabled="disabled" ';
				if($column->getMaxLength() > 255)
					$htmlFields .='</textarea>';
				else
					$htmlFields .=' />';				
			}


        	if ($errors->has($formField))
          		$htmlFields .= '<span class="help-block">'.$errors->first($formField).'</span>';
    
			$htmlFields .= '</div>';
		}

		return $htmlFields;
	}
	public static function buildShowFromDM($dataModel, $item)
	{
		$htmlFields = "";
		$columns = $dataModel->getColumns();
		foreach($columns as $key => $column)
		{
			$value = ($item->$key != null)?$item->$key : '-';
			$htmlFields .='<div class="col-md-4"><strong>'.$column->getLabel().'</strong> </div><div class="col-md-8">'.$value.'</div>';
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
			$columnName = $columns[$tableField]->getName();
			$title = $columns[ $tableField ]->getLabel();
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
				$columnName = $columns[$tableField]->getName();
				$value = $item->$columnName;
				$htmlFields .= '<td>'.$value.'</td>';

			}
			$htmlFields .= '<td class="td w-clearfix" >';
			$htmlFields .= '<a class="btn btn-primary btn-sm" style="margin-right:6px" href="/'.lcfirst($dataModel->getName()).'/'.$item->id.'/edit">Edit</a>';
			$htmlFields .= '<a class="btn btn-success btn-sm" href="/'.lcfirst($dataModel->getName()).'/'.$item->id.'">Show</a>';
			$htmlFields .= '<form method="POST" class="btn btn-sm" style="margin-left:-6px" action="/'.lcfirst($dataModel->getName()).'/'.$item->id.'">
                     '.csrf_field().method_field('DELETE').'
                      <button type="submit" class="btn btn-danger btn-sm ">Delete</button>
                    </form>';
			$htmlFields .= '</tr>';
		}	
		$htmlFields .= '</tbody>';

		$htmlFields .= '</table>';

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

