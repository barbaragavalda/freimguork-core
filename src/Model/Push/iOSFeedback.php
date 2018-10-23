<?php

namespace Core\Model\Push;

use Core\Utils\Config;

class iOSFeedback extends iOS {

    public function __construct(){
        parent::__construct( 'feedback.sandbox.push.apple.com' );
    }

    public function send(){
        if( $this->apns == false) {
            $this->close();
            echo "APNS Feedback Request Error";
        }else{
            $feedback_tokens = array();
            while( !feof($this->apns) ){
                $data = fread($this->apns, 38);
                if( strlen($data) ){
                    $feedback_tokens[] = unpack("N1timestamp/n1length/H*devtoken", $data);

                }
            }
            fclose($this->apns);

            if( count($feedback_tokens) ){
                r($feedback_tokens);
                /*
                foreach ($feedback_tokens as $k => $token) {
                    // code to delete record from database
                }
                 */
            }
        }
    }

}
