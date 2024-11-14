<?php

/**
 * @group fao
 */
class FAO_Custom extends WP_UnitTestCase{
    public $wp_query;

    /**
	 * Set up the test fixture
	 */
	public function set_up() {
		parent::set_up();

        $this->wp_query = $GLOBALS['wp_query'];

        //register the custom post type
        register_post_type('report', array(
            'public' => true,
            'label' => 'report',
            'supports' => array('title', 'editor', 'thumbnail')
        ));
        /**
         * create custom query vars
         */
        //sort
        add_filter('query_vars', function($vars){
            $vars[] = 'sort';
            return $vars;
        });
        //comm_type
        add_filter('query_vars', function($vars){
            $vars[] = 'comm_type';
            return $vars;
        });
        //topic
        add_filter('query_vars', function($vars){
            $vars[] = 'topic';
            return $vars;
        });
        //series
        add_filter('query_vars', function($vars){
            $vars[] = 'series';
            return $vars;
        });



    }


    /**
     * Tear down the test fixture
     */
    public function tear_down() {
        parent::tear_down();
        //remove the custom post type
        unregister_post_type('report');
        //remove the custom query var
        remove_filter('query_vars', function($vars){
            $vars[] = 'sort';
            return $vars;
        });
    }


    /**
     * functions.php method filter_order and filter_orderby
     * line 111-118
     */

    public function filter_order($sort) {
        if(str_contains($sort, 'asc')){
            return 'ASC';
        }
        return NULL;
    }
    
    public function filter_orderby($sort) {
    
        if(str_contains($sort, 'title')){
            return 'title';
        }
        return NULL;
    }





    /**
     * testing wp_core to see if we can set the query vars
     */
    public function test_custom_query_var(){
        //stub the global wp object
        global $wp_query;
        //stub the global wp object
        $wp_query = $this->wp_query;
        
        $wp_query->query_vars['sort'] = 'asc';


        $this->assertNotNull(get_query_var('sort'));
    }

    /*-----------------testing core sort functionality-----------------*/
    /**
     * testing that we can sort order and orderby given the assumption that the query var is set
     * function convert_sort_to_order_and_orderby($query)
     */
    public function test_asc_sort(){
        global $wp_query;
        //reset the wp_query to the local class, since globals reset per every test
        $wp_query = $this->wp_query;

        $wp_query->query_vars['sort'] = 'asc';

        //test if it did set the query var
        $this->assertNotNull(get_query_var('sort'));

        if(isset($wp_query->query_vars['sort'])){
            $sort = $wp_query->query_vars['sort'];
            $order = $this->filter_order($sort);
            $orderby = $this->filter_orderby($sort);
            $this->assertStringContainsString('ASC', $order);
            $this->assertNull($orderby);
        }
    }


    /**
     * testing that we can modify the custom query var based off the server request
     */
    public function test_server_uri_to_query_var(){
        global $wp_query;
        //reset the wp_query to the local class, since globals reset per every test
        $wp_query = $this->wp_query;

        //stub the server request
        $_SERVER['REQUEST_URI'] = '/?sort=asc';

        //test if it did set the query var
        $this->assertNotNull(get_query_var('sort'));

    }

    /**
     * consider a core functionality; functions.php function get_archive_post_type
     * line 657-681
     */
    public function get_archive_post_type($wp_query){
        $current_post_type = get_post_type();
        if (empty($current_post_type) or $current_post_type == 'post') {
          //if get_post_type() is not set, check the query_vars['post_type']
          if ( !empty($wp_query->query_vars['post_type']) ) {
            $current_post_type = $wp_query->query_vars['post_type'];
          }else{
            // if query_vars['post_type'] is not set, check the other query vars
            $topic_is_set = !empty($wp_query->query_vars['topic']) ? $wp_query->query_vars['topic'] : false;
            $series_is_set = !empty($wp_query->query_vars['series']) ? $wp_query->query_vars['series'] : false;
            $comm_type_is_set = !empty($wp_query->query_vars['comm_type']) ? $wp_query->query_vars['comm_type'] : false;
            if ( $comm_type_is_set and ($series_is_set or $topic_is_set) ) {
              $current_post_type = 'all';
            }elseif ($topic_is_set) {
              $current_post_type = 'report';
            }elseif ($series_is_set) {
              $current_post_type = 'report';
            }elseif ($comm_type_is_set) {
              $current_post_type = 'communication';
            }
          }
        }
        return $current_post_type;
    }

    /**
     * testing that we can get the archive post type
     */
    public function test_get_archive_post_type(){
        global $wp_query;
        //reset the wp_query to the local class, since globals reset per every test
        $wp_query = $this->wp_query;

        //stub the query_vars
        $wp_query->query_vars['post_type'] = 'report';
        $wp_query->query_vars['topic'] = 'a valid topic';
        $wp_query->query_vars['series'] = 'a valid series';
        $wp_query->query_vars['comm_type'] = 'a valid comm_type';

        $current_post_type = $this->get_archive_post_type($wp_query);

        //test post type
        $this->assertStringContainsString('report', $current_post_type);
    }
        
}