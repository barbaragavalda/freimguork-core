<?php

namespace Core\Model\Push;

use Core\Utils\Config;

class Push {

    /**
     * @var string  deep linking protocol
     */
    protected $urlScheme = '';

    public function __construct(){
        // url scheme
        $config = Config::getInstance();
        $webserviceConfig = $config->get('webservice');
        $this->urlScheme = $webserviceConfig['url_scheme'];
    }

    /**
     * send push notification
     * @param $platforms
     * @param $message
     * @param string $deepLink
     */
    public function send($platforms, $message, $deepLink = ''){
        if( $deepLink != '' ) $urlScheme = $this->urlScheme . $deepLink;

        $android = $ios = array();
        foreach($platforms as $platform){
            $tokens = explode(',', $platform['tokens']);
            switch( $platform['name'] ){
                case 'android': $android = $tokens; break;
                case 'ios':     $ios = $tokens;     break;
            }
        }

        if( count($android) ){
            $pushAndroid = new Android($message, $android, $deepLink);
            $pushAndroid->send();
            $pushAndroid->close();
        }

        if( count($ios) ){
            $pushiOS = new iOS($message, $ios, $deepLink);
            $pushiOS->send();
            $pushiOS->close();
        }

    }

}