<?php 
namespace Barista;

class BaristaBuilder{
	public static function start()
	{


	}
	public static function buildFromDM($dataModel)
	{
		$htmlFields = "";
		$columns = $dataModel->getColumns();
		$formFields = $dataModel->getFormFields();

		foreach ($formFields as $formField) {
			$fieldInformation = $columns[$formField];
			   // <div class="form-group @if(!$column->editable) {{'disabled'}} @endif  {{ $errors->has($formField) ? ' has-error' : '' }}">
			$htmlFields .= '<div class="form-group">';
			$htmlFields .='<label class="control-label" for="'.$fieldInformation->name.'">';

			$htmlFields .= $fieldInformation->showName;
			$htmlFields .= ($fieldInformation->required)?' <strong>*</strong>':'';
			$htmlFields .= '</label>';

			if(isset($fieldInformation->maxlength) && $fieldInformation->maxlength > 255)
				$htmlFields .= '<textarea ';
			else
				$htmlFields .= '<input ';
			$htmlFields .='class="form-control" name="'.$fieldInformation->name.'" id="'.$fieldInformation->name.'"';

			if(isset($fieldInformation->maxlength) && $fieldInformation->maxlength > 255)
				$htmlFields .='</textarea>';
			else
				$htmlFields .=' />';

			$htmlFields .= '</div>';
		}
		echo $htmlFields;
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
  /*

      @if(!isset( $foreigns[$formField] ) )
          @if(isset($column->maxLength) && $column->maxLength > 255)
            <textarea name="{{ $formField }}"
          @if(isset($column->maxLength))
            maxlength= "{{ $column->maxLength }}" 
          @endif
          name="{{ $formField }}" 
          placeholder="" 
          @if($column->required)
            required="required"
          @endif
          @if(!$column->editable)
            {{'disabled'}}
          @endif
             >
          @if(isset($item))
            {{ $item->$formField }}
          @endif
             </textarea>
          @else
          <input class="form-control"  
          @if(isset($column->maxLength))
            maxlength= "{{ $column->maxLength }}" 
          @endif


          name="{{ $formField }}" 
          placeholder="" ""
          type="{{ $column->fieldType }}" 
          @if(isset($item))
            value="{{ $item->$formField }}"
          @endif
          @if($column->required)
            required="required"
          @endif
          @if(!$column->editable)
            {{'disabled'}}
          @endif
          >
          @endif
      @else 
        <select class="form-control" name="{{ $formField }}" >
          @foreach($foreignDatas[$formField] as $foreignData)
            @if(isset($item))
              @if( ($item->$formField == $foreignData->name) || ($item->$formField == $foreignData->id) )
              {{-- || ( isset( $item->$formField->name) && $item->$formField->id == $foreignData->id)  --}}
                <option value="{{ $foreignData->id }}" selected>{{ $foreignData->name }}</option>
              @else
                <option value="{{ $foreignData->id }}">{{ $foreignData->name }}</option>
              @endif
            @else
               <option value="{{ $foreignData->id }}">{{ $foreignData->name }}</option>
            @endif
          @endforeach
          </select>
      @endif
      </div>
        @if ($errors->has($formField))
          <span class="help-block">{{ $errors->first($formField) }}</span>
        @endif
  @endforeach
  */
?>

