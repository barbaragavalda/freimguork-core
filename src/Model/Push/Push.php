<?php

namespace Core\Model\Push;

use Appacman\Model\Push\Statistic;
use Appacman\Model\Utils\Admin;
use Core\Model\File;
use Core\Model\Model;
use Core\Model\Utils\Mail;
use Core\Utils\Config;

class Push extends Model
{

    /**
     * @var string  app name
     */
    protected $appName = '';

    /**
     * @var string  deep linking protocol
     */
    protected $urlScheme = '';

    /**
     * @var bool  save push statistics
     */
    private $hasStatistics = false;

    /**
     * @var bool  save should log push
     */
    private $doLog = true;

    /**
     * @var string
     */
    private $reportEmail = '';

    public function __construct($doLog = false)
    {
        parent::__construct();

        $this->doLog = $doLog;

        // url scheme
        $config            = Config::getInstance();
        $webserviceConfig  = $config->get('webservice');
        $this->appName     = $webserviceConfig['app_name'];
        $this->urlScheme   = $webserviceConfig['url_scheme'];
        $this->reportEmail = $config->get('push', 'report');

        $this->hasStatistics = $this->mysql->tableExists('appacman_push_statistic');
    }

    /**
     * send push notification
     *
     * @param       $platforms
     * @param       $message
     * @param array $notification
     * @param int   $pushID
     */
    public function send($platforms, $message, $notification, $pushID = null)
    {
        $deepLink = $notification['deeplink'];
        if ($pushID && $this->hasStatistics) {
            $this->id = $pushID;
        }

        if (array_key_exists('id', $notification)) {
            $pushID = $notification['id'];
        }
        if ($pushID) {
            if ($deepLink && strpos($deepLink, '?') !== false) {
                $deepLink .= '&';
            } else {
                $deepLink .= '?';
            }
            $deepLink .= 'pushID=' . $pushID;
        }

        $urlScheme = '';
        if ($deepLink != '') {
            $urlScheme = $this->urlScheme . $deepLink;
        }

        $image   = '';
        $imageID = array_key_exists('image', $notification) ? $notification['image'] : '';
        if ($imageID) {
            $file  = new File($imageID);
            $image = $file->getAbsolutePath();
        }

        $android = $ios = array();
        foreach ($platforms as $platform) {
            $tokens = explode(',', $platform['tokens']);
            switch ($platform['name']) {
                case 'android':
                    $android = $tokens;
                    break;
                case 'ios':
                    $ios = $tokens;
                    break;
            }
        }

        $devices      = 0;
        $emailMessage = '<p><b>Texto: </b>' . $message . '</p>';
        if (count($android)) {
            if (IS_DEV) {
                echo '<p>Fake push notification for Android: <b>' . $message . '</b></p>';
            } else {
                $pushAndroid = new Android($message, $android, $urlScheme, $image, $this->doLog);
                $pushAndroid->send();
                $pushAndroid->close();

                $emailMessage .= '<p><b>Android: </b>' . $pushAndroid->getEmailResult() . '</p>';
                $devices      += $pushAndroid->getOk();
            }
        }

        if (count($ios)) {
            if (IS_DEV) {
                echo '<p>Fake push notification for iOS: <b>' . $message . '</b></p>';
            } else {
                $pushiOS = new iOSPush($message, $ios, $urlScheme, $image, $this->doLog);
                $pushiOS->send();
                $pushiOS->close();

                $emailMessage .= '<p><b>iOS: </b>' . $pushiOS->getEmailResult() . '</p>';
                $devices      += $pushiOS->getOk();
            }
        }

        $this->statistics($message, $emailMessage, $devices);
    }

    private function statistics($message, $emailMessage, $devices)
    {
        if ($message && $emailMessage && $this->doLog) {
            $admin = new Admin();
            $email = new Mail();
            $email->send(
                null,
                $admin->getEmails(),
                'Estadísticas notificaciones push',
                $emailMessage
            );
        }

        if ($this->hasStatistics) {
            $statistics = new Statistic($this->id);
            $statistics->update($devices);
        }
    }

}
