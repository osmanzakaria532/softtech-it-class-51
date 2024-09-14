<?php

    add_action('cmb2_admin_init', 'custom_metaboxes_function');

    function custom_metaboxes_function(){

        // start here
        $metaBox = new_cmb2_box(array(
            'object_types' => array('post', 'page'),
            'title' => 'Additional Fields',
            'id' => 'additional-fields'

        ));

        $metaBox -> add_field(array(
            'id' => 'sub-title',
            'name' => 'Sub title',
            'default' => 'standard value (optional)',
            'type' => 'text',
        ));
        
        $metaBox -> add_field(array(
            'id' => 'sub-description',
            'name' => 'Sub Description',
            'type'    => 'wysiwyg',
            'options' => array(
                'wpautop' => true,
                'textarea_rows' => get_option('default_post_edit_rows', 8),
            ),
        ));
        // ends here

        // starts here
        $arekta = new_cmb2_box(array(
            'object_types' => array('basic-testimonials'),
            'title' => 'Additional Fields',
            'id' => 'arekta'

        ));

        $arekta -> add_field(array(
            'id' => 'sub-title',
            'name' => 'Description',
            'type' => 'text',
        ));
        
        $arekta -> add_field(array(
            'name'    => 'Test Tinymce',
            'id'      => 'wiki_test_wysiwyg',
            'type'    => 'wysiwyg',
            'options' => array(
                // 'wpautop' => true,
                'textarea_rows' => get_option('default_post_edit_rows', 8),
            ),
        ));
    }