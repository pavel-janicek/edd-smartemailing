<?php
use ADT\getSmartEmailing;
if (!class_exists('Clvr_SmartEmailing')){

    
    class Clvr_SmartEmailing 
    {
        const META_KEY = 'edd_smartemailing_customer_id';
        const SUBSCRIBE = 'subscribe';
        const UNSUBSCRIBE = 'unsubscribe';
        const COMPLETE = 'complete';
        const PENDING = 'pending';
        private $smartEmailing;
        private $context = 'edd_smartemailing';
        private $API_LOGIN = 'martina@mywii.cz';
        private $API_TOKEN = "p5t52qa8go23i6b9szxu8cje1powhnsss1rgcfuj";

        function __construct(){
            require 'class_clvr_smartemailing_settings.php';
            add_action('init',array($this,'dump'));
            add_action( 'edd_insert_payment', array($this,'createContact'));
            add_action( 'edd_complete_purchase', array($this, 'updateContact') );
            new Clvr_SmartEmailing_Settings();
        }

        public function dump(){
            if (isset($_GET['listener']) && ($_GET['listener']) == 'eddsmartemailing' ){
                $payment_id = 70;

                $user_info = edd_get_payment_meta_user_info( $payment_id );
                print_r($user_info);
                exit;
            }
        }

        public function createContact($payment_id){
            if ($this->isInitialized()){
                $meta = get_post_meta ($payment_id, '_edd_payment_meta', true);
                $downloads = $meta['downloads'];
                $lists = $this->sanitizeLists($downloads,self::SUBSCRIBE,self::PENDING);
                $sE_lists = $this->smartEmailingLists($lists);
                $user_info = edd_get_payment_meta_user_info( $payment_id );
                $email = $user_info['email'];
                $properties = [
                    'name' => $user_info['first_name'],
                    'surname' => $user_info['last_name']
                ];
                try{
                    $response = $this->getSmartEmailing()->importContact($email,$sE_lists,$properties);  
                    $smartEmailing_id = $response['contacts_map'][0]['contact_id'];
                    return $smartEmailing_id;
                }catch(Exception $e){
                    return false;
                    
                }
            }else{
                return false;
            }
        }

        public function getFullLists(){
            return $this->getSmartEmailing()->getContactlists();
        }

        public function getLists(){

            if ($this->isInitialized()){
                $lists_to_return = [];
                $lists = $this->getSmartEmailing()->getContactlists();
                if (empty($lists)){
                    $lists_to_return[-1] = 'V SmartEmailingu nejsou seznamy';
                    return $lists_to_return;
                }
                foreach ($lists['data'] as $list){
                    $lists_to_return[$list['id']] = $list['name'];
                }
                return $lists_to_return;

            }else{
                return [
                    -1 => 'SmartEmailing není propojen'
                ];
            }
        }

        public function getSmartEmailing(){
            global $edd_options;
            if (!empty($this->smartEmailing)){
                return $this->smartEmailing;   
            }
            $this->smartEmailing = new ADT\SmartEmailing($edd_options[$this->context. '_login'],$edd_options[$this->context. '_token']);
            return $this->smartEmailing;
        }

        public function isInitialized(){

            try{
                $this->getSmartEmailing()->checkCredentials();
                return true;
            }catch(Exception $e){
                return false;
            }

        }

        public function smartEmailingLists($lists,$action ='subscribe'){
            $se_Action = 'confirmed';
            if ($action == 'unsubscribe'){
                $se_Action = 'unsubscribed';
            }

            $final_lists = [];
            foreach ($lists as $key => $value) {
                $final_lists[$value] = $se_Action;
            }
            return $final_lists;

        }

        public function sanitizeLists($cart,$action,$payment_status){
            global $edd_options;
            $lists =[];
            $key = $this->context.'_list_'.$action.'_'.$payment_status;
            if (!$edd_options[$this->context. '_default_segment']){
                $lists[] = $edd_options[$key];                
            }
            foreach ($cart as $cart_item){                
                $lists[] =get_post_meta($cart_item['id'],$key,true);
            }
            $sanitized_list = array();
            foreach ($lists as $list_item){
                if (is_array($list_item)){
                    foreach($list_item as $key=>$value){
                        $sanitized_list[]=$value;
                    }
                }else{
                    $sanitized_list[]=$list_item;
        
                }
            }
            return array_unique($sanitized_list);
        }

    

        

        public function updateContact($payment_id){
            if ($this->isInitialized()){
                $payment      = new EDD_Payment( $payment_id );
                $cart = $payment->downloads;
                $lists = $this->sanitizeLists($cart,self::SUBSCRIBE,self::COMPLETE);
                $sE_lists = $this->smartEmailingLists($lists);
                $user_info = edd_get_payment_meta_user_info( $payment_id );
                $email = $user_info['email'];
                $properties = [
                    'name' => $user_info['first_name'],
                    'surname' => $user_info['last_name']
                ];
                try{
                    $response = $this->getSmartEmailing()->importContact($email,$sE_lists,$properties);  
                    $smartEmailing_id = $response['contacts_map'][0]['contact_id'];
                    return $smartEmailing_id;
                }catch(Exception $e){
                    return false;
                }
            }else{
                return false;
            }
        }

        
        
    }//class
    
}//class exists