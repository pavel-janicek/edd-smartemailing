<?php

if (!class_exists('Clvr_SmartEmailing_Settings')){
    class Clvr_SmartEmailing_Settings extends Clvr_SmartEmailing
    {
        
        private $context = 'edd_smartemailing';
        private $actions = ['subscribe','unsubscribe'];
        private $payment_statuses = ['pending','complete'];

        function __construct(){
            $this->smartEmailing = $smartEmailing;
            add_filter( 'edd_settings_sections_extensions', array($this,'settings_section') );
            add_filter( 'edd_settings_extensions', array($this,'add_settings') );
            add_action( 'add_meta_boxes', array($this,'add_meta_boxes'));
            add_filter( 'edd_metabox_fields_save', array($this,'save_metabox') );

        }

        public function settings_section($sections){
			$sections[$this->context.'-settings'] = __( 'Nastavení SmartEmailing', $this->context );
			return $sections;
		}

        /*
        * @return Clvr_SmartEmailing class
        *
        */
        

        public function add_settings( $settings ) {

            $smartEmailing_settings = array(
                array(
                    'id' => $this->context. '_settings',
                    'name' => '<strong>' . __( 'Nastavení SmartEmailing', $this->context ) . '</strong>',
                    'desc' => __( 'Nastavte propojení se SmartEmailing', $this->context ),
                    'type' => 'header'
                ),
                array(
                    'id' => $this->context. '_login',
                    'name' =>  __( 'Přihlašovací e-mail', $this->context ) ,
                    'desc' => __( 'Přihlašovací e-mail', $this->context ),
                    'type' => 'text',
                    'size' => 'regular'
        
                ),
                array(
                    'id' => $this->context. '_token',
                    'name' =>  __( 'API token', $this->context ) ,
                    'desc' => __( 'API token', $this->context  ),
                    'type' => 'text',
                    'size' => 'regular'
        
                ),
               array(
                    'id'      => $this->context. '_list_subscribe_pending',
                    'name'    => __( 'Výchozí Segment po objednání', $this->context),
                    'desc'    => __( 'Výchozí Segment po objednání', $this->context ),
                    'type'    => 'select',
                    'options' => $this->getLists()
                ),
                array(
                     'id'      => $this->context. '_list_subscribe_complete',
                     'name'    => __( 'Výchozí Segment po zaplacení', $this->context),
                     'desc'    => __( 'Výchozí Segment po zaplacení', $this->context ),
                     'type'    => 'select',
                     'options' => $this->getLists()
                ),
               array(
                    'id'       => $this->context. '_default_segment',
                    'name'     => __( 'Nepoužívat výchozí segmenty', 'edd_mautic'),
                    'desc'     => __( 'If you select this, you will have to segment users per download', 'edd_mautic' ),
                    'type'    => 'checkbox',
                ),      
        
        
            );

            if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
				$smartEmailing_settings = array( $this->context.'-settings' => $smartEmailing_settings );
			}
        
            return array_merge( $settings, $smartEmailing_settings );
        }

        public function add_meta_boxes(){
            if ( current_user_can( 'edit_product', get_the_ID() ) ) {
                
                foreach ($this->payment_statuses as $payment_status) {
                        $id = $this->context.'_list_'.$action.'_'.$payment_status;
                        $sentence = 'Segmenty ' .$this->translateAction('subscribe') . $this->translatePaymentStatus($payment_status);
                        $args =[
                            'action' => 'subscribe',
                            'payment_status' => $payment_status,
                            'sentence' => $sentence
                        ];
                        add_meta_box($id,$sentence,array($this,'render_metabox'), 'download','side','default',$args);
                        
                }
                
            }
        }

        public function render_metabox($post,$data){
            $name = $this->context . '_list_' . $data['args']['action'] . '_' . $data['args']['payment_status'];
            $checked = (array)get_post_meta($post->ID,$name,true);
            $lists = $this->getLists();
            foreach ($lists as $list_id => $list_name){
                echo '<label>';
                echo '<input type="checkbox" name="'. $name.'[]" value="'.$list_id.'"' . checked(true, in_array($list_id,$checked), false) .'>';
                echo '&nbsp;' . $list_name;
                echo '</label><br />';
            }
        }

        public function save_metabox($fields){
            foreach ($this->actions as $action){
                foreach ($this->payment_statuses as $payment_status){
                    $name = $this->context . '_list_' . $action. '_' .$payment_status;
                    $fields[] = $name;
                }
            }
            return $fields;

        }

        public function translateAction($action){
            $action_cz = '';
            if ($action == 'unsubscribe'){
                $action_cz = 'pro odhlášení ';
            }
            return $action_cz;
        }

        public function translatePaymentStatus($payment_status){
            $status = 'po objednání';
            if ($payment_status =='complete'){
                $status = 'po zaplacení';
            }
            return $status;
        }
        
        
    } //end class
    
} //end if class exists