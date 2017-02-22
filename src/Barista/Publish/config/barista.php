<?php 
return [
	/*
    |--------------------------------------------------------------------------
    | Admin Route Prefix
    |--------------------------------------------------------------------------
    | 
    | This is a prefix for admin dashboard. For example 'admin' will be the prefix for routes like /admin/users
    | 
    */
	'prefix' => '',
	/*
    |--------------------------------------------------------------------------
    | General Form Rules
    |--------------------------------------------------------------------------
    | 
    | General rules for Barista to generate your HTML code properly.
    | 
    */
 	'should_group_form' => true,
	/*
    |--------------------------------------------------------------------------
    | General Form Classes
    |--------------------------------------------------------------------------
    | 
    | This part is all about general div classes will be added to your HTML.
    | 
    */
	'group_class' => 'columns',
	'group_label_class' => 'column is-4',
	'group_input_class' => 'column is-8',

	/*
    |--------------------------------------------------------------------------
    | General Form Classes
    |--------------------------------------------------------------------------
    | 
    | This part is all about general div classes will be added to your HTML.
    | 
    */
	'show_group_class' => 'columns',
	'show_group_label_class' => 'column is-2',
	'show_group_value_class' => 'column is-10',

	'error_block_class' => 'help-block',
	'information_block_class' => 'help-block',
	//'required_block_class' => 'required',
	'required_block_class' => 'tag is-warning',
	'link_class' => '',
	'select_class' => 'form-control',
	'textarea_class' => 'form-control',
	//'input_class' => 'form-control',
	'input_class' => 'input',
	'checkbox_class' => 'checkbox',
	'label_class' => 'label',

	/*
    |--------------------------------------------------------------------------
    | Button Classes
    |--------------------------------------------------------------------------
    | 
    | Button classes for form and datatable.
    | 
    */	
	//'btn_class' => 'btn',
	'btn_class' => 'button',
	//'btn_class_sm' => 'btn-sm',
	'btn_sm_class' => 'is-small',
	//'btn_primary' => 'btn-primary',
	'btn_primary' => 'is-primary',
	//'btn_success' => 'btn-success',
	'btn_success' => 'is-success',
	//'btn_danger' => 'btn-danger',
	'btn_danger' => 'is-danger',
	//'btn_info' => 'btn-info',
	'btn_info' => 'is-info',
	'btn_additional_class' => '',

	/*
    |--------------------------------------------------------------------------
    | Table Classes
    |--------------------------------------------------------------------------
    | 
    | This part is all about tables.
    | 
    */
	'table_class' => 'table compact',
	'thead_class' => 'thead-inverse',
		//'btn_class' => 'btn',
	'tbl_btn_class' => 'button',
	//'btn_class_sm' => 'btn-sm',
	'tbl_btn_sm_class' => 'is-small',
	//'btn_primary' => 'btn-primary',
	'tbl_btn_primary' => 'is-primary',
	//'btn_success' => 'btn-success',
	'tbl_btn_success' => 'is-success',
	//'btn_danger' => 'btn-danger',
	'tbl_btn_danger' => 'is-danger',
	//'btn_info' => 'btn-info',
	'tbl_btn_info' => 'is-info',
	'tbl_btn_additional_class' => 'is-outlined',

	/*
    |--------------------------------------------------------------------------
    | Barista Strings
    |--------------------------------------------------------------------------
    | 
    | This part is all about strings.
    | 
    */	
	'required_tag' => 'required',

];