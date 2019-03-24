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
    'prefix'                   => '',

    /*
    |--------------------------------------------------------------------------
    | Translation
    |--------------------------------------------------------------------------
    |
    | lang en text.php inside fields key.
    |
    */
    'trans_file'               => 'text',
    'trans_file_key'           => 'fields',

    /*
    |--------------------------------------------------------------------------
    | General Form Rules
    |--------------------------------------------------------------------------
    | 
    | General rules for Barista to generate your HTML code properly.
    | 
    */
    'should_group_form'        => true,

    /*
    |--------------------------------------------------------------------------
    | Float Rules
    |--------------------------------------------------------------------------
    |
    | Float rules for Barista
    |
    */
    'pull_left'                => 'pull-left',
    'pull_right'               => 'pull-right',

    /*
    |--------------------------------------------------------------------------
    | General Form Classes
    |--------------------------------------------------------------------------
    | 
    | This part is all about general div classes will be added to your HTML.
    | 
    */
    'group_class'              => 'form-group',
    'group_label_class'        => '',
    'group_input_class'        => '',

    /*
    |--------------------------------------------------------------------------
    | General Form Classes
    |--------------------------------------------------------------------------
    | 
    | This part is all about general div classes will be added to your HTML.
    | 
    */
    'show_group_class'         => 'row',
    'show_group_label_class'   => 'col-md-4',
    'show_group_value_class'   => 'col-md-8',
    'show_value_class'         => 'control-value',
    'show_value_tag'           => 'span',
    'error_block_class'        => 'help-block',
    'error_text_class'         => 'help is-danger',
    'information_block_class'  => 'help-block',
    'required_block_class'     => 'required',
    'link_class'               => '',
    'select_class'             => 'form-control',
    'textarea_class'           => 'form-control',
    'input_class'              => 'form-control',
    'checkbox_class'           => 'checkbox',
    'label_class'              => 'control-label',

    /*
    |--------------------------------------------------------------------------
    | Button Classes
    |--------------------------------------------------------------------------
    | 
    | Button classes for form and datatable.
    | 
    */
    'btn_class'                => 'btn',
    'btn_sm_class'             => 'btn-sm',
    'btn_primary'              => 'btn-primary',
    'btn_success'              => 'btn-success',
    'btn_danger'               => 'btn-danger',
    'btn_info'                 => 'btn-info',
    'btn_cancel'               => 'btn-default',
    'btn_additional_class'     => '',

    /*
    |--------------------------------------------------------------------------
    | Table Classes
    |--------------------------------------------------------------------------
    | 
    | This part is all about tables.
    | 
    */
    'table_class'              => 'table compact',
    'thead_class'              => 'thead-inverse',
    'tbl_btn_class'            => 'btn',
    'tbl_btn_sm_class'         => 'btn-sm',
    'tbl_btn_primary'          => 'btn-primary',
    'tbl_btn_success'          => 'btn-success',
    'tbl_btn_danger'           => 'btn-danger',
    'tbl_btn_info'             => 'btn-info',
    'tbl_btn_additional_class' => '',

    /*
    |--------------------------------------------------------------------------
    | Barista Strings
    |--------------------------------------------------------------------------
    | 
    | This part is all about strings.
    | 
    */
    'required_tag'             => 'required',

];