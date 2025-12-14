<?php
/**
 * KiwiCommerce
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please contact us https://kiwicommerce.co.uk/contacts.
 *
 * @category   KiwiCommerce
 * @package    MageOS_AdminActivityLog
 * @copyright  Copyright (C) 2018 Kiwi Commerce Ltd (https://kiwicommerce.co.uk/)
 * @license    https://kiwicommerce.co.uk/magento2-extension-license/
 */

namespace MageOS\AdminActivityLog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class Browser
 * @package MageOS\AdminActivityLog\Helper
 */
class Browser extends AbstractHelper implements \Stringable
{
    /**
     * @var string
     */
    private $agent = '';

    /**
     * @var string
     */
    private $browser_name = '';

    private ?string $version = '';

    /**
     * @var string
     */
    private $platform = '';

    private string $os = '';

    /**
     * @var bool
     */
    private $is_aol = false;

    /**
     * @var bool
     */
    private $is_mobile = false;

    /**
     * @var bool
     */
    private $is_tablet = false;

    /**
     * @var bool
     */
    private $is_robot = false;

    /**
     * @var bool
     */
    private $is_facebook = false;

    private ?string $aol_version = '';

    public const BROWSER_UNKNOWN = 'unknown';
    public const VERSION_UNKNOWN = 'unknown';

    public const BROWSER_OPERA = 'Opera'; // http://www.opera.com/
    public const BROWSER_OPERA_MINI = 'Opera Mini'; // http://www.opera.com/mini/
    public const BROWSER_WEBTV = 'WebTV'; // http://www.webtv.net/pc/
    public const BROWSER_EDGE = 'Edge'; // https://www.microsoft.com/edge
    public const BROWSER_IE = 'Internet Explorer'; // http://www.microsoft.com/ie/
    public const BROWSER_POCKET_IE = 'Pocket Internet Explorer'; // http://en.wikipedia.org/wiki/Internet_Explorer_Mobile
    public const BROWSER_KONQUEROR = 'Konqueror'; // http://www.konqueror.org/
    public const BROWSER_ICAB = 'iCab'; // http://www.icab.de/
    public const BROWSER_OMNIWEB = 'OmniWeb'; // http://www.omnigroup.com/applications/omniweb/
    public const BROWSER_FIREBIRD = 'Firebird'; // http://www.ibphoenix.com/
    public const BROWSER_FIREFOX = 'Firefox'; // http://www.mozilla.com/en-US/firefox/firefox.html
    public const BROWSER_ICEWEASEL = 'Iceweasel'; // http://www.geticeweasel.org/
    public const BROWSER_SHIRETOKO = 'Shiretoko'; // http://wiki.mozilla.org/Projects/shiretoko
    public const BROWSER_MOZILLA = 'Mozilla'; // http://www.mozilla.com/en-US/
    public const BROWSER_AMAYA = 'Amaya'; // http://www.w3.org/Amaya/
    public const BROWSER_LYNX = 'Lynx'; // http://en.wikipedia.org/wiki/Lynx
    public const BROWSER_SAFARI = 'Safari'; // http://apple.com
    public const BROWSER_IPHONE = 'iPhone'; // http://apple.com
    public const BROWSER_IPOD = 'iPod'; // http://apple.com
    public const BROWSER_IPAD = 'iPad'; // http://apple.com
    public const BROWSER_CHROME = 'Chrome'; // http://www.google.com/chrome
    public const BROWSER_ANDROID = 'Android'; // http://www.android.com/
    public const BROWSER_GOOGLEBOT = 'GoogleBot'; // http://en.wikipedia.org/wiki/Googlebot

    public const BROWSER_YANDEXBOT = 'YandexBot'; // http://yandex.com/bots
    public const BROWSER_YANDEXIMAGERESIZER_BOT = 'YandexImageResizer'; // http://yandex.com/bots
    public const BROWSER_YANDEXIMAGES_BOT = 'YandexImages'; // http://yandex.com/bots
    public const BROWSER_YANDEXVIDEO_BOT = 'YandexVideo'; // http://yandex.com/bots
    public const BROWSER_YANDEXMEDIA_BOT = 'YandexMedia'; // http://yandex.com/bots
    public const BROWSER_YANDEXBLOGS_BOT = 'YandexBlogs'; // http://yandex.com/bots
    public const BROWSER_YANDEXFAVICONS_BOT = 'YandexFavicons'; // http://yandex.com/bots
    public const BROWSER_YANDEXWEBMASTER_BOT = 'YandexWebmaster'; // http://yandex.com/bots
    public const BROWSER_YANDEXDIRECT_BOT = 'YandexDirect'; // http://yandex.com/bots
    public const BROWSER_YANDEXMETRIKA_BOT = 'YandexMetrika'; // http://yandex.com/bots
    public const BROWSER_YANDEXNEWS_BOT = 'YandexNews'; // http://yandex.com/bots
    public const BROWSER_YANDEXCATALOG_BOT = 'YandexCatalog'; // http://yandex.com/bots

    public const BROWSER_SLURP = 'Yahoo! Slurp'; // http://en.wikipedia.org/wiki/Yahoo!_Slurp
    public const BROWSER_W3CVALIDATOR = 'W3C Validator'; // http://validator.w3.org/
    public const BROWSER_BLACKBERRY = 'BlackBerry'; // http://www.blackberry.com/
    public const BROWSER_ICECAT = 'IceCat'; // http://en.wikipedia.org/wiki/GNU_IceCat
    public const BROWSER_NOKIA_S60 = 'Nokia S60 OSS Browser'; // http://en.wikipedia.org/wiki/Web_Browser_for_S60
    public const BROWSER_NOKIA = 'Nokia Browser'; // * all other WAP-based browsers on the Nokia Platform
    public const BROWSER_MSN = 'MSN Browser'; // http://explorer.msn.com/
    public const BROWSER_MSNBOT = 'MSN Bot'; // http://search.msn.com/msnbot.htm
    public const BROWSER_BINGBOT = 'Bing Bot'; // http://en.wikipedia.org/wiki/Bingbot
    public const BROWSER_VIVALDI = 'Vivalidi'; // https://vivaldi.com/
    public const BROWSER_YANDEX = 'Yandex'; // https://browser.yandex.ua/

    public const BROWSER_NETSCAPE_NAVIGATOR = 'Netscape Navigator'; // http://browser.netscape.com/ (DEPRECATED)
    public const BROWSER_GALEON = 'Galeon'; // http://galeon.sourceforge.net/ (DEPRECATED)
    public const BROWSER_NETPOSITIVE = 'NetPositive'; // http://en.wikipedia.org/wiki/NetPositive (DEPRECATED)
    public const BROWSER_PHOENIX = 'Phoenix'; // http://en.wikipedia.org/wiki/History_of_Mozilla_Firefox (DEPRECATED)
    public const BROWSER_PLAYSTATION = "PlayStation";
    public const BROWSER_SAMSUNG = "SamsungBrowser";
    public const BROWSER_SILK = "Silk";
    public const BROWSER_I_FRAME = "Iframely";
    public const BROWSER_COCOA = "CocoaRestClient";

    public const PLATFORM_UNKNOWN = 'unknown';
    public const PLATFORM_WINDOWS = 'Windows';
    public const PLATFORM_WINDOWS_CE = 'Windows CE';
    public const PLATFORM_APPLE = 'Apple';
    public const PLATFORM_LINUX = 'Linux';
    public const PLATFORM_OS2 = 'OS/2';
    public const PLATFORM_BEOS = 'BeOS';
    public const PLATFORM_IPHONE = 'iPhone';
    public const PLATFORM_IPOD = 'iPod';
    public const PLATFORM_IPAD = 'iPad';
    public const PLATFORM_BLACKBERRY = 'BlackBerry';
    public const PLATFORM_NOKIA = 'Nokia';
    public const PLATFORM_FREEBSD = 'FreeBSD';
    public const PLATFORM_OPENBSD = 'OpenBSD';
    public const PLATFORM_NETBSD = 'NetBSD';
    public const PLATFORM_SUNOS = 'SunOS';
    public const PLATFORM_OPENSOLARIS = 'OpenSolaris';
    public const PLATFORM_ANDROID = 'Android';
    public const PLATFORM_PLAYSTATION = "Sony PlayStation";
    public const PLATFORM_ROKU = "Roku";
    public const PLATFORM_APPLE_TV = "Apple TV";
    public const PLATFORM_TERMINAL = "Terminal";
    public const PLATFORM_FIRE_OS = "Fire OS";
    public const PLATFORM_SMART_TV = "SMART-TV";
    public const PLATFORM_CHROME_OS = "Chrome OS";
    public const PLATFORM_JAVA_ANDROID = "Java/Android";
    public const PLATFORM_POSTMAN = "Postman";
    public const PLATFORM_I_FRAME = "Iframely";

    public const OPERATING_SYSTEM_UNKNOWN = 'unknown';

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->reset();
    }

    /**
     * Reset all properties
     * @return void
     */
    public function reset(): void
    {
        $this->agent = $_SERVER['HTTP_USER_AGENT'] ?? "";
        $this->browser_name = self::BROWSER_UNKNOWN;
        $this->version = self::VERSION_UNKNOWN;
        $this->platform = self::PLATFORM_UNKNOWN;
        $this->os = self::OPERATING_SYSTEM_UNKNOWN;
        $this->is_aol = false;
        $this->is_mobile = false;
        $this->is_tablet = false;
        $this->is_robot = false;
        $this->is_facebook = false;
        $this->aol_version = self::VERSION_UNKNOWN;
    }

    /**
     * Check to see if the specific browser is valid
     * @param string $browserName
     * @return bool True if the browser is the specified browser
     */
    public function isBrowser($browserName): bool
    {
        return strcasecmp($this->browser_name, trim((string)$browserName)) === 0;
    }

    /**
     * The name of the browser.  All return types are from the class contants
     * @return string Name of the browser
     */
    public function getBrowser()
    {
        return $this->browser_name;
    }

    /**
     * Set the name of the browser
     * @param $browser string The name of the Browser
     * @return void
     */
    public function setBrowser($browser): void
    {
        $this->browser_name = $browser;
    }

    /**
     * The name of the platform.  All return types are from the class contants
     * @return string Name of the browser
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set the name of the platform
     * @param string $platform The name of the Platform
     * @return void
     */
    public function setPlatform($platform): void
    {
        $this->platform = $platform;
    }

    /**
     * The version of the browser.
     * @return string Version of the browser (will only contain alpha-numeric characters and a period)
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Set the version of the browser
     * @param string $version The version of the Browser
     * @return void
     */
    public function setVersion($version): void
    {
        $this->version = preg_replace('/[^0-9,.,a-z,A-Z-]/', '', $version);
    }

    /**
     * The version of AOL.
     * @return string Version of AOL (will only contain alpha-numeric characters and a period)
     */
    public function getAolVersion(): ?string
    {
        return $this->aol_version;
    }

    /**
     * Set the version of AOL
     * @param string $version The version of AOL
     * @return void
     */
    public function setAolVersion($version): void
    {
        $this->aol_version = preg_replace('/[^0-9,.,a-z,A-Z]/', '', $version);
    }

    /**
     * Is the browser from AOL?
     * @return bool True if the browser is from AOL otherwise false
     */
    public function isAol()
    {
        return $this->is_aol;
    }

    /**
     * Is the browser from a mobile device?
     * @return bool True if the browser is from a mobile device otherwise false
     */
    public function isMobile()
    {
        return $this->is_mobile;
    }

    /**
     * Is the browser from a tablet device?
     * @return bool True if the browser is from a tablet device otherwise false
     */
    public function isTablet()
    {
        return $this->is_tablet;
    }

    /**
     * Is the browser from a robot (ex Slurp,GoogleBot)?
     * @return bool True if the browser is from a robot otherwise false
     */
    public function isRobot()
    {
        return $this->is_robot;
    }

    /**
     * Is the browser from facebook?
     * @return bool True if the browser is from facebook otherwise false
     */
    public function isFacebook()
    {
        return $this->is_facebook;
    }

    /**
     * Set the browser to be from AOL
     * @param $isAol
     * @return void
     */
    public function setAol($isAol): void
    {
        $this->is_aol = $isAol;
    }

    /**
     * Set the Browser to be mobile
     * @param bool $value is the browser a mobile browser or not
     * @return void
     */
    protected function setMobile($value = true)
    {
        $this->is_mobile = $value;
    }

    /**
     * Set the Browser to be tablet
     * @param bool $value is the browser a tablet browser or not
     * @return void
     */
    protected function setTablet($value = true)
    {
        $this->is_tablet = $value;
    }

    /**
     * Set the Browser to be a robot
     * @param bool $value is the browser a robot or not
     * @return void
     */
    protected function setRobot($value = true)
    {
        $this->is_robot = $value;
    }

    /**
     * Set the Browser to be a Facebook request
     * @param bool $value is the browser a robot or not
     * @return void
     */
    protected function setFacebook($value = true)
    {
        $this->is_facebook = $value;
    }

    /**
     * Get the user agent value in use to determine the browser
     * @return string The user agent from the HTTP header
     */
    public function getUserAgent()
    {
        return $this->agent;
    }

    /**
     * Set the user agent value (the construction will use the HTTP header value - this will overwrite it)
     * @param string $agent_string The value for the User Agent
     * @return void
     */
    public function setUserAgent($agent_string): void
    {
        $this->reset();
        $this->agent = $agent_string;
        $this->determine();
    }

    /**
     * Used to determine if the browser is actually "chromeframe"
     * @return bool True if the browser is using chromeframe
     * @since 1.7
     */
    public function isChromeFrame(): bool
    {
        return (str_contains($this->agent, "chromeframe"));
    }

    /**
     * Returns a formatted string with a summary of the details of the browser.
     * @return string formatted string with a summary of the browser
     */
    public function __toString(): string
    {
        $device = ($this->isMobile()) ? 'Mobile' : (($this->isTablet()) ? 'Tablet' : 'Desktop');
        return "<strong>Browser Name:</strong> {$this->getBrowser()}<br/>\n" .
            "<strong>Browser Version:</strong> {$this->getVersion()}<br/>\n" .
            "<strong>Platform:</strong> {$this->getPlatform()}<br/>\n" .
            "<strong>Device:</strong> {$device}";
    }

    /**
     * Protected routine to calculate and determine what the browser is in use (including platform)
     * @return void
     */
    protected function determine()
    {
        $this->checkPlatform();
        $this->checkBrowsers();
        $this->checkForAol();
    }

    /**
     * Protected routine to determine the browser type
     * @return bool True if the browser was detected otherwise false
     */
    protected function checkBrowsers(): bool
    {
        return (
            // well-known, well-used
            // Special Notes:
            // (1) Opera must be checked before FireFox due to the odd
            //     user agents used in some older versions of Opera
            // (2) WebTV is strapped onto Internet Explorer so we must
            //     check for WebTV before IE
            // (3) (deprecated) Galeon is based on Firefox and needs to be
            //     tested before Firefox is tested
            // (4) OmniWeb is based on Safari so OmniWeb check must occur
            //     before Safari
            // (5) Netscape 9+ is based on Firefox so Netscape checks
            //     before FireFox are necessary
            // (6) Vivalid is UA contains both Firefox and Chrome so Vivalid checks
            //     before Firefox and Chrome
            $this->checkBrowserWebTv() ||
            $this->checkBrowserEdge() ||
            $this->checkBrowserInternetExplorer() ||
            $this->checkBrowserOpera() ||
            $this->checkBrowserGaleon() ||
            $this->checkBrowserNetscapeNavigator9Plus() ||
            $this->checkBrowserVivaldi() ||
            $this->checkBrowserYandex() ||
            $this->checkBrowserFirefox() ||
            $this->checkBrowserChrome() ||
            $this->checkBrowserOmniWeb() ||

            // common mobile
            $this->checkBrowserAndroid() ||
            $this->checkBrowseriPad() ||
            $this->checkBrowseriPod() ||
            $this->checkBrowseriPhone() ||
            $this->checkBrowserBlackBerry() ||
            $this->checkBrowserNokia() ||

            // common bots
            $this->checkBrowserGoogleBot() ||
            $this->checkBrowserMSNBot() ||
            $this->checkBrowserBingBot() ||
            $this->checkBrowserSlurp() ||

            // Yandex bots
            $this->checkBrowserYandexBot() ||
            $this->checkBrowserYandexImageResizerBot() ||
            $this->checkBrowserYandexBlogsBot() ||
            $this->checkBrowserYandexCatalogBot() ||
            $this->checkBrowserYandexDirectBot() ||
            $this->checkBrowserYandexFaviconsBot() ||
            $this->checkBrowserYandexImagesBot() ||
            $this->checkBrowserYandexMediaBot() ||
            $this->checkBrowserYandexMetrikaBot() ||
            $this->checkBrowserYandexNewsBot() ||
            $this->checkBrowserYandexVideoBot() ||
            $this->checkBrowserYandexWebmasterBot() ||

            // check for facebook external hit when loading URL
            $this->checkFacebookExternalHit() ||

            // WebKit base check (post mobile and others)
            $this->checkBrowserSamsung() ||
            $this->checkBrowserSilk() ||
            $this->checkBrowserSafari() ||

            // everyone else
            $this->checkBrowserNetPositive() ||
            $this->checkBrowserFirebird() ||
            $this->checkBrowserKonqueror() ||
            $this->checkBrowserIcab() ||
            $this->checkBrowserPhoenix() ||
            $this->checkBrowserAmaya() ||
            $this->checkBrowserLynx() ||
            $this->checkBrowserShiretoko() ||
            $this->checkBrowserIceCat() ||
            $this->checkBrowserIceweasel() ||
            $this->checkBrowserW3CValidator() ||
            $this->checkBrowserPlayStation() ||
            $this->checkBrowserIframely() ||
            $this->checkBrowserCocoa() ||
            $this->checkBrowserMozilla() /* Mozilla is such an open standard that you must check it last */

        );
    }

    /**
     * Determine if the user is using a BlackBerry (last updated 1.7)
     * @return bool True if the browser is the BlackBerry browser otherwise false
     */
    protected function checkBrowserBlackBerry(): bool
    {
        if (stripos($this->agent, 'blackberry') !== false) {
            $aresult = explode("/", stristr($this->agent, "BlackBerry"));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->browser_name = self::BROWSER_BLACKBERRY;
                $this->setMobile();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the user is using an AOL User Agent (last updated 1.7)
     * @return bool True if the browser is from AOL otherwise false
     */
    protected function checkForAol(): bool
    {
        $this->setAol(false);
        $this->setAolVersion(self::VERSION_UNKNOWN);

        if (stripos($this->agent, 'aol') !== false) {
            $aversion = explode(' ', stristr($this->agent, 'AOL'));
            if (isset($aversion[1])) {
                $this->setAol(true);
                $this->setAolVersion(preg_replace('/[^0-9\.a-z]/i', '', $aversion[1]));
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the GoogleBot or not (last updated 1.7)
     * @return bool True if the browser is the GoogletBot otherwise false
     */
    protected function checkBrowserGoogleBot(): bool
    {
        if (stripos($this->agent, 'googlebot') !== false) {
            $aresult = explode('/', stristr($this->agent, 'googlebot'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_GOOGLEBOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexBot or not
     * @return bool True if the browser is the YandexBot otherwise false
     */
    protected function checkBrowserYandexBot(): bool
    {
        if (stripos($this->agent, 'YandexBot') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexBot'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXBOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexImageResizer or not
     * @return bool True if the browser is the YandexImageResizer otherwise false
     */
    protected function checkBrowserYandexImageResizerBot(): bool
    {
        if (stripos($this->agent, 'YandexImageResizer') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexImageResizer'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXIMAGERESIZER_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexCatalog or not
     * @return bool True if the browser is the YandexCatalog otherwise false
     */
    protected function checkBrowserYandexCatalogBot(): bool
    {
        if (stripos($this->agent, 'YandexCatalog') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexCatalog'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXCATALOG_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexNews or not
     * @return bool True if the browser is the YandexNews otherwise false
     */
    protected function checkBrowserYandexNewsBot(): bool
    {
        if (stripos($this->agent, 'YandexNews') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexNews'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXNEWS_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexMetrika or not
     * @return bool True if the browser is the YandexMetrika otherwise false
     */
    protected function checkBrowserYandexMetrikaBot(): bool
    {
        if (stripos($this->agent, 'YandexMetrika') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexMetrika'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXMETRIKA_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexDirect or not
     * @return bool True if the browser is the YandexDirect otherwise false
     */
    protected function checkBrowserYandexDirectBot(): bool
    {
        if (stripos($this->agent, 'YandexDirect') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexDirect'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXDIRECT_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexWebmaster or not
     * @return bool True if the browser is the YandexWebmaster otherwise false
     */
    protected function checkBrowserYandexWebmasterBot(): bool
    {
        if (stripos($this->agent, 'YandexWebmaster') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexWebmaster'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXWEBMASTER_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexFavicons or not
     * @return bool True if the browser is the YandexFavicons otherwise false
     */
    protected function checkBrowserYandexFaviconsBot(): bool
    {
        if (stripos($this->agent, 'YandexFavicons') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexFavicons'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXFAVICONS_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexBlogs or not
     * @return bool True if the browser is the YandexBlogs otherwise false
     */
    protected function checkBrowserYandexBlogsBot(): bool
    {
        if (stripos($this->agent, 'YandexBlogs') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexBlogs'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXBLOGS_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexMedia or not
     * @return bool True if the browser is the YandexMedia otherwise false
     */
    protected function checkBrowserYandexMediaBot(): bool
    {
        if (stripos($this->agent, 'YandexMedia') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexMedia'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXMEDIA_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexVideo or not
     * @return bool True if the browser is the YandexVideo otherwise false
     */
    protected function checkBrowserYandexVideoBot(): bool
    {
        if (stripos($this->agent, 'YandexVideo') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexVideo'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXVIDEO_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the YandexImages or not
     * @return bool True if the browser is the YandexImages otherwise false
     */
    protected function checkBrowserYandexImagesBot(): bool
    {
        if (stripos($this->agent, 'YandexImages') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YandexImages'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(';', '', $aversion[0]));
                $this->browser_name = self::BROWSER_YANDEXIMAGES_BOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the MSNBot or not (last updated 1.9)
     * @return bool True if the browser is the MSNBot otherwise false
     */
    protected function checkBrowserMSNBot(): bool
    {
        if (stripos($this->agent, "msnbot") !== false) {
            $aresult = explode("/", stristr($this->agent, "msnbot"));
            if (isset($aresult[1])) {
                $aversion = explode(" ", $aresult[1]);
                $this->setVersion(str_replace(";", "", $aversion[0]));
                $this->browser_name = self::BROWSER_MSNBOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the BingBot or not (last updated 1.9)
     * @return bool True if the browser is the BingBot otherwise false
     */
    protected function checkBrowserBingBot(): bool
    {
        if (stripos($this->agent, "bingbot") !== false) {
            $aresult = explode("/", stristr($this->agent, "bingbot"));
            if (isset($aresult[1])) {
                $aversion = explode(" ", $aresult[1]);
                $this->setVersion(str_replace(";", "", $aversion[0]));
                $this->browser_name = self::BROWSER_BINGBOT;
                $this->setRobot();
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is the W3C Validator or not (last updated 1.7)
     * @return bool True if the browser is the W3C Validator otherwise false
     */
    protected function checkBrowserW3CValidator(): bool
    {
        if (stripos($this->agent, 'W3C-checklink') !== false) {
            $aresult = explode('/', stristr($this->agent, 'W3C-checklink'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->browser_name = self::BROWSER_W3CVALIDATOR;
                return true;
            }
        } elseif (stripos($this->agent, 'W3C_Validator') !== false) {
            // Some of the Validator versions do not delineate w/ a slash - add it back in
            $ua = str_replace("W3C_Validator ", "W3C_Validator/", $this->agent);
            $aresult = explode('/', stristr($ua, 'W3C_Validator'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->browser_name = self::BROWSER_W3CVALIDATOR;
                return true;
            }
        } elseif (stripos($this->agent, 'W3C-mobileOK') !== false) {
            $this->browser_name = self::BROWSER_W3CVALIDATOR;
            $this->setMobile();
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is the Yahoo! Slurp Robot or not (last updated 1.7)
     * @return bool True if the browser is the Yahoo! Slurp Robot otherwise false
     */
    protected function checkBrowserSlurp(): bool
    {
        if (stripos($this->agent, 'slurp') !== false) {
            $aresult = explode('/', stristr($this->agent, 'Slurp'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->browser_name = self::BROWSER_SLURP;
                $this->setRobot();
                $this->setMobile(false);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Edge or not
     * @return bool True if the browser is Edge otherwise false
     */
    protected function checkBrowserEdge(): bool
    {
        if (stripos($this->agent, 'Edge/') !== false) {
            $aresult = explode('/', stristr($this->agent, 'Edge'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_EDGE);
                if (stripos($this->agent, 'Windows Phone') !== false || stripos($this->agent, 'Android') !== false) {
                    $this->setMobile();
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Internet Explorer or not (last updated 1.7)
     * @return bool True if the browser is Internet Explorer otherwise false
     */
    protected function checkBrowserInternetExplorer(): bool
    {
        //  Test for IE11
        if (stripos($this->agent, 'Trident/7.0; rv:11.0') !== false) {
            $this->setBrowser(self::BROWSER_IE);
            $this->setVersion('11.0');
            return true;
        } // Test for v1 - v1.5 IE
        elseif (stripos($this->agent, 'microsoft internet explorer') !== false) {
            $this->setBrowser(self::BROWSER_IE);
            $this->setVersion('1.0');
            $aresult = stristr($this->agent, '/');
            if (preg_match('/308|425|426|474|0b1/i', $aresult)) {
                $this->setVersion('1.5');
            }
            return true;
        } // Test for versions > 1.5
        elseif (stripos($this->agent, 'msie') !== false && stripos($this->agent, 'opera') === false) {
            // See if the browser is the odd MSN Explorer
            if (stripos($this->agent, 'msnb') !== false) {
                $aresult = explode(' ', stristr(str_replace(';', '; ', $this->agent), 'MSN'));
                if (isset($aresult[1])) {
                    $this->setBrowser(self::BROWSER_MSN);
                    $this->setVersion(str_replace(['(', ')', ';'], '', $aresult[1]));
                    return true;
                }
            }
            $aresult = explode(' ', stristr(str_replace(';', '; ', $this->agent), 'msie'));
            if (isset($aresult[1])) {
                $this->setBrowser(self::BROWSER_IE);
                $this->setVersion(str_replace(['(', ')', ';'], '', $aresult[1]));
                if (preg_match('#trident/([0-9\.]+);#i', $this->agent, $aresult)) {
                    if ($aresult[1] == '3.1') {
                        $this->setVersion('7.0');
                    } else {
                        if ($aresult[1] == '4.0') {
                            $this->setVersion('8.0');
                        } else {
                            if ($aresult[1] == '5.0') {
                                $this->setVersion('9.0');
                            } else {
                                if ($aresult[1] == '6.0') {
                                    $this->setVersion('10.0');
                                } else {
                                    if ($aresult[1] == '7.0') {
                                        $this->setVersion('11.0');
                                    } else {
                                        if ($aresult[1] == '8.0') {
                                            $this->setVersion('11.0');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if (stripos($this->agent, 'IEMobile') !== false) {
                    $this->setBrowser(self::BROWSER_POCKET_IE);
                    $this->setMobile();
                }
                return true;
            }
        } // Test for versions > IE 10
        else {
            if (stripos($this->agent, 'trident') !== false) {
                $this->setBrowser(self::BROWSER_IE);
                $result = explode('rv:', $this->agent);
                if (isset($result[1])) {
                    $this->setVersion(preg_replace('/[^0-9.]+/', '', $result[1]));
                    $this->agent = str_replace(["Mozilla", "Gecko"], "MSIE", $this->agent);
                }
            } // Test for Pocket IE
            else {
                if (stripos($this->agent, 'mspie') !== false || stripos($this->agent, 'pocket') !== false) {
                    $aresult = explode(' ', stristr($this->agent, 'mspie'));
                    if (isset($aresult[1])) {
                        $this->setPlatform(self::PLATFORM_WINDOWS_CE);
                        $this->setBrowser(self::BROWSER_POCKET_IE);
                        $this->setMobile();

                        if (stripos($this->agent, 'mspie') !== false) {
                            $this->setVersion($aresult[1]);
                        } else {
                            $aversion = explode('/', $this->agent);
                            if (isset($aversion[1])) {
                                $this->setVersion($aversion[1]);
                            }
                        }
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Opera or not (last updated 1.7)
     * @return bool True if the browser is Opera otherwise false
     */
    protected function checkBrowserOpera(): bool
    {
        if (stripos($this->agent, 'opera mini') !== false) {
            $resultant = stristr($this->agent, 'opera mini');
            if (preg_match('/\//', $resultant)) {
                $aresult = explode('/', $resultant);
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $this->setVersion($aversion[0]);
                }
            } else {
                $aversion = explode(' ', stristr($resultant, 'opera mini'));
                if (isset($aversion[1])) {
                    $this->setVersion($aversion[1]);
                }
            }
            $this->browser_name = self::BROWSER_OPERA_MINI;
            $this->setMobile();
            return true;
        } else {
            if (stripos($this->agent, 'opera') !== false) {
                $resultant = stristr($this->agent, 'opera');
                if (preg_match('/Version\/(1*.*)$/', $resultant, $matches)) {
                    $this->setVersion($matches[1]);
                } else {
                    if (preg_match('/\//', $resultant)) {
                        $aresult = explode('/', str_replace("(", " ", $resultant));
                        if (isset($aresult[1])) {
                            $aversion = explode(' ', $aresult[1]);
                            $this->setVersion($aversion[0]);
                        }
                    } else {
                        $aversion = explode(' ', stristr($resultant, 'opera'));
                        $this->setVersion($aversion[1] ?? "");
                    }
                }
                if (stripos($this->agent, 'Opera Mobi') !== false) {
                    $this->setMobile();
                }
                $this->browser_name = self::BROWSER_OPERA;
                return true;
            } else {
                if (stripos($this->agent, 'OPR') !== false) {
                    $resultant = stristr($this->agent, 'OPR');
                    if (preg_match('/\//', $resultant)) {
                        $aresult = explode('/', str_replace("(", " ", $resultant));
                        if (isset($aresult[1])) {
                            $aversion = explode(' ', $aresult[1]);
                            $this->setVersion($aversion[0]);
                        }
                    }
                    if (stripos($this->agent, 'Mobile') !== false) {
                        $this->setMobile();
                    }
                    $this->browser_name = self::BROWSER_OPERA;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Chrome or not (last updated 1.7)
     * @return bool True if the browser is Chrome otherwise false
     */
    protected function checkBrowserChrome(): bool
    {
        if (stripos($this->agent, 'Chrome') !== false) {
            $aresult = explode('/', stristr($this->agent, 'Chrome'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_CHROME);
                //Chrome on Android
                if (stripos($this->agent, 'Android') !== false) {
                    if (stripos($this->agent, 'Mobile') !== false) {
                        $this->setMobile();
                    } else {
                        $this->setTablet();
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is WebTv or not (last updated 1.7)
     * @return bool True if the browser is WebTv otherwise false
     */
    protected function checkBrowserWebTv(): bool
    {
        if (stripos($this->agent, 'webtv') !== false) {
            $aresult = explode('/', stristr($this->agent, 'webtv'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_WEBTV);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is NetPositive or not (last updated 1.7)
     * @return bool True if the browser is NetPositive otherwise false
     */
    protected function checkBrowserNetPositive(): bool
    {
        if (stripos($this->agent, 'NetPositive') !== false) {
            $aresult = explode('/', stristr($this->agent, 'NetPositive'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion(str_replace(['(', ')', ';'], '', $aversion[0]));
                $this->setBrowser(self::BROWSER_NETPOSITIVE);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Galeon or not (last updated 1.7)
     * @return bool True if the browser is Galeon otherwise false
     */
    protected function checkBrowserGaleon(): bool
    {
        if (stripos($this->agent, 'galeon') !== false) {
            $aresult = explode(' ', stristr($this->agent, 'galeon'));
            $aversion = explode('/', $aresult[0]);
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_GALEON);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Konqueror or not (last updated 1.7)
     * @return bool True if the browser is Konqueror otherwise false
     */
    protected function checkBrowserKonqueror(): bool
    {
        if (stripos($this->agent, 'Konqueror') !== false) {
            $aresult = explode(' ', stristr($this->agent, 'Konqueror'));
            $aversion = explode('/', $aresult[0]);
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_KONQUEROR);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is iCab or not (last updated 1.7)
     * @return bool True if the browser is iCab otherwise false
     */
    protected function checkBrowserIcab(): bool
    {
        if (stripos($this->agent, 'icab') !== false) {
            $aversion = explode(' ', stristr(str_replace('/', ' ', $this->agent), 'icab'));
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_ICAB);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is OmniWeb or not (last updated 1.7)
     * @return bool True if the browser is OmniWeb otherwise false
     */
    protected function checkBrowserOmniWeb(): bool
    {
        if (stripos($this->agent, 'omniweb') !== false) {
            $aresult = explode('/', stristr($this->agent, 'omniweb'));
            $aversion = explode(' ', $aresult[1] ?? "");
            $this->setVersion($aversion[0]);
            $this->setBrowser(self::BROWSER_OMNIWEB);
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Phoenix or not (last updated 1.7)
     * @return bool True if the browser is Phoenix otherwise false
     */
    protected function checkBrowserPhoenix(): bool
    {
        if (stripos($this->agent, 'Phoenix') !== false) {
            $aversion = explode('/', stristr($this->agent, 'Phoenix'));
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_PHOENIX);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Firebird or not (last updated 1.7)
     * @return bool True if the browser is Firebird otherwise false
     */
    protected function checkBrowserFirebird(): bool
    {
        if (stripos($this->agent, 'Firebird') !== false) {
            $aversion = explode('/', stristr($this->agent, 'Firebird'));
            if (isset($aversion[1])) {
                $this->setVersion($aversion[1]);
                $this->setBrowser(self::BROWSER_FIREBIRD);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Netscape Navigator 9+ or not (last updated 1.7)
     * NOTE: (http://browser.netscape.com/ - Official support ended on March 1st, 2008)
     * @return bool True if the browser is Netscape Navigator 9+ otherwise false
     */
    protected function checkBrowserNetscapeNavigator9Plus(): bool
    {
        if (stripos($this->agent, 'Firefox') !== false && preg_match(
            '/Navigator\/([^ ]*)/i',
            $this->agent,
            $matches
        )) {
            $this->setVersion($matches[1]);
            $this->setBrowser(self::BROWSER_NETSCAPE_NAVIGATOR);
            return true;
        } else {
            if (stripos($this->agent, 'Firefox') === false && preg_match(
                '/Netscape6?\/([^ ]*)/i',
                $this->agent,
                $matches
            )) {
                $this->setVersion($matches[1]);
                $this->setBrowser(self::BROWSER_NETSCAPE_NAVIGATOR);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Shiretoko or not (https://wiki.mozilla.org/Projects/shiretoko) (last updated 1.7)
     * @return bool True if the browser is Shiretoko otherwise false
     */
    protected function checkBrowserShiretoko(): bool
    {
        if (stripos($this->agent, 'Mozilla') !== false && preg_match(
            '/Shiretoko\/([^ ]*)/i',
            $this->agent,
            $matches
        )) {
            $this->setVersion($matches[1]);
            $this->setBrowser(self::BROWSER_SHIRETOKO);
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Ice Cat or not (http://en.wikipedia.org/wiki/GNU_IceCat) (last updated 1.7)
     * @return bool True if the browser is Ice Cat otherwise false
     */
    protected function checkBrowserIceCat(): bool
    {
        if (stripos($this->agent, 'Mozilla') !== false && preg_match('/IceCat\/([^ ]*)/i', $this->agent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser(self::BROWSER_ICECAT);
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Nokia or not (last updated 1.7)
     * @return bool True if the browser is Nokia otherwise false
     */
    protected function checkBrowserNokia(): bool
    {
        if (preg_match("/Nokia([^\/]+)\/([^ SP]+)/i", $this->agent, $matches)) {
            $this->setVersion($matches[2]);
            if (stripos($this->agent, 'Series60') !== false || str_contains($this->agent, 'S60')) {
                $this->setBrowser(self::BROWSER_NOKIA_S60);
            } else {
                $this->setBrowser(self::BROWSER_NOKIA);
            }
            $this->setMobile();
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Firefox or not (last updated 1.7)
     * @return bool True if the browser is Firefox otherwise false
     */
    protected function checkBrowserFirefox(): bool
    {
        if (stripos($this->agent, 'safari') === false) {
            if (preg_match("/Firefox[\/ \(]([^ ;\)]+)/i", $this->agent, $matches)) {
                $this->setVersion($matches[1]);
                $this->setBrowser(self::BROWSER_FIREFOX);
                //Firefox on Android
                if (stripos($this->agent, 'Android') !== false) {
                    if (stripos($this->agent, 'Mobile') !== false) {
                        $this->setMobile();
                    } else {
                        $this->setTablet();
                    }
                }
                return true;
            } else {
                if (preg_match("/Firefox$/i", $this->agent, $matches)) {
                    $this->setVersion("");
                    $this->setBrowser(self::BROWSER_FIREFOX);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Firefox or not (last updated 1.7)
     * @return bool True if the browser is Firefox otherwise false
     */
    protected function checkBrowserIceweasel(): bool
    {
        if (stripos($this->agent, 'Iceweasel') !== false) {
            $aresult = explode('/', stristr($this->agent, 'Iceweasel'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_ICEWEASEL);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Mozilla or not (last updated 1.7)
     * @return bool True if the browser is Mozilla otherwise false
     */
    protected function checkBrowserMozilla(): bool
    {
        if (stripos($this->agent, 'mozilla') !== false && preg_match(
            '/rv:[0-9].[0-9][a-b]?/i',
            $this->agent
        ) && stripos($this->agent, 'netscape') === false) {
            $aversion = explode(' ', stristr($this->agent, 'rv:'));
            preg_match('/rv:[0-9].[0-9][a-b]?/i', $this->agent, $aversion);
            $this->setVersion(str_replace('rv:', '', $aversion[0]));
            $this->setBrowser(self::BROWSER_MOZILLA);
            return true;
        } else {
            if (stripos($this->agent, 'mozilla') !== false && preg_match(
                '/rv:[0-9]\.[0-9]/i',
                $this->agent
            ) && stripos($this->agent, 'netscape') === false) {
                $aversion = explode('', stristr($this->agent, 'rv:'));
                $this->setVersion(str_replace('rv:', '', $aversion[0]));
                $this->setBrowser(self::BROWSER_MOZILLA);
                return true;
            } else {
                if (stripos($this->agent, 'mozilla') !== false && preg_match(
                    '/mozilla\/([^ ]*)/i',
                    $this->agent,
                    $matches
                ) && stripos($this->agent, 'netscape') === false) {
                    $this->setVersion($matches[1]);
                    $this->setBrowser(self::BROWSER_MOZILLA);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Lynx or not (last updated 1.7)
     * @return bool True if the browser is Lynx otherwise false
     */
    protected function checkBrowserLynx(): bool
    {
        if (stripos($this->agent, 'lynx') !== false) {
            $aresult = explode('/', stristr($this->agent, 'Lynx'));
            $aversion = explode(' ', ($aresult[1] ?? ""));
            $this->setVersion($aversion[0]);
            $this->setBrowser(self::BROWSER_LYNX);
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Amaya or not (last updated 1.7)
     * @return bool True if the browser is Amaya otherwise false
     */
    protected function checkBrowserAmaya(): bool
    {
        if (stripos($this->agent, 'amaya') !== false) {
            $aresult = explode('/', stristr($this->agent, 'Amaya'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_AMAYA);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Safari or not (last updated 1.7)
     * @return bool True if the browser is Safari otherwise false
     */
    protected function checkBrowserSafari(): bool
    {
        if (stripos($this->agent, 'Safari') !== false
            && stripos($this->agent, 'iPhone') === false
            && stripos($this->agent, 'iPod') === false
        ) {
            $aresult = explode('/', stristr($this->agent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion(self::VERSION_UNKNOWN);
            }
            $this->setBrowser(self::BROWSER_SAFARI);
            return true;
        }
        return false;
    }

    /**
     * Check browser
     * @return bool
     */
    protected function checkBrowserSamsung(): bool
    {
        if (stripos($this->agent, 'SamsungBrowser') !== false) {
            $aresult = explode('/', stristr($this->agent, 'SamsungBrowser'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion(self::VERSION_UNKNOWN);
            }
            $this->setBrowser(self::BROWSER_SAMSUNG);
            return true;
        }
        return false;
    }

    /**
     * Check browser
     * @return bool
     */
    protected function checkBrowserSilk(): bool
    {
        if (stripos($this->agent, 'Silk') !== false) {
            $aresult = explode('/', stristr($this->agent, 'Silk'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion(self::VERSION_UNKNOWN);
            }
            $this->setBrowser(self::BROWSER_SILK);
            return true;
        }
        return false;
    }

    /**
     * Check browser
     * @return bool
     */
    protected function checkBrowserIframely(): bool
    {
        if (stripos($this->agent, 'Iframely') !== false) {
            $aresult = explode('/', stristr($this->agent, 'Iframely'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion(self::VERSION_UNKNOWN);
            }
            $this->setBrowser(self::BROWSER_I_FRAME);
            return true;
        }
        return false;
    }

    /**
     * Check browser
     * @return bool
     */
    protected function checkBrowserCocoa(): bool
    {
        if (stripos($this->agent, 'CocoaRestClient') !== false) {
            $aresult = explode('/', stristr($this->agent, 'CocoaRestClient'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion(self::VERSION_UNKNOWN);
            }
            $this->setBrowser(self::BROWSER_COCOA);
            return true;
        }
        return false;
    }

    /**
     * Detect if URL is loaded from FacebookExternalHit
     * @return bool True if it detects FacebookExternalHit otherwise false
     */
    protected function checkFacebookExternalHit(): bool
    {
        if (stristr($this->agent, 'FacebookExternalHit')) {
            $this->setRobot();
            $this->setFacebook();
            return true;
        }
        return false;
    }

    /**
     * Detect if URL is being loaded from internal Facebook browser
     * @return bool True if it detects internal Facebook browser otherwise false
     */
    protected function checkForFacebookIos(): bool
    {
        if (stristr($this->agent, 'FBIOS')) {
            $this->setFacebook();
            return true;
        }
        return false;
    }

    /**
     * Detect Version for the Safari browser on iOS devices
     * @return bool True if it detects the version correctly otherwise false
     */
    protected function getSafariVersionOnIos(): bool
    {
        $aresult = explode('/', stristr($this->agent, 'Version'));
        if (isset($aresult[1])) {
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            return true;
        }
        return false;
    }

    /**
     * Detect Version for the Chrome browser on iOS devices
     * @return bool True if it detects the version correctly otherwise false
     */
    protected function getChromeVersionOnIos(): bool
    {
        $aresult = explode('/', stristr($this->agent, 'CriOS'));
        if (isset($aresult[1])) {
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser(self::BROWSER_CHROME);
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is iPhone or not (last updated 1.7)
     * @return bool True if the browser is iPhone otherwise false
     */
    protected function checkBrowseriPhone(): bool
    {
        if (stripos($this->agent, 'iPhone') !== false) {
            $this->setVersion(self::VERSION_UNKNOWN);
            $this->setBrowser(self::BROWSER_IPHONE);
            $this->getSafariVersionOnIos();
            $this->getChromeVersionOnIos();
            $this->checkForFacebookIos();
            $this->setMobile();
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is iPad or not (last updated 1.7)
     * @return bool True if the browser is iPad otherwise false
     */
    protected function checkBrowseriPad(): bool
    {
        if (stripos($this->agent, 'iPad') !== false) {
            $this->setVersion(self::VERSION_UNKNOWN);
            $this->setBrowser(self::BROWSER_IPAD);
            $this->getSafariVersionOnIos();
            $this->getChromeVersionOnIos();
            $this->checkForFacebookIos();
            $this->setTablet();
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is iPod or not (last updated 1.7)
     * @return bool True if the browser is iPod otherwise false
     */
    protected function checkBrowseriPod(): bool
    {
        if (stripos($this->agent, 'iPod') !== false) {
            $this->setVersion(self::VERSION_UNKNOWN);
            $this->setBrowser(self::BROWSER_IPOD);
            $this->getSafariVersionOnIos();
            $this->getChromeVersionOnIos();
            $this->checkForFacebookIos();
            $this->setMobile();
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Android or not (last updated 1.7)
     * @return bool True if the browser is Android otherwise false
     */
    protected function checkBrowserAndroid(): bool
    {
        if (stripos($this->agent, 'Android') !== false) {
            $aresult = explode(' ', stristr($this->agent, 'Android'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion(self::VERSION_UNKNOWN);
            }
            if (stripos($this->agent, 'Mobile') !== false) {
                $this->setMobile();
            } else {
                $this->setTablet();
            }
            $this->setBrowser(self::BROWSER_ANDROID);
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Vivaldi
     * @return bool True if the browser is Vivaldi otherwise false
     */
    protected function checkBrowserVivaldi(): bool
    {
        if (stripos($this->agent, 'Vivaldi') !== false) {
            $aresult = explode('/', stristr($this->agent, 'Vivaldi'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_VIVALDI);
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the browser is Yandex
     * @return bool True if the browser is Yandex otherwise false
     */
    protected function checkBrowserYandex(): bool
    {
        if (stripos($this->agent, 'YaBrowser') !== false) {
            $aresult = explode('/', stristr($this->agent, 'YaBrowser'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
                $this->setBrowser(self::BROWSER_YANDEX);

                if (stripos($this->agent, 'iPad') !== false) {
                    $this->setTablet();
                } elseif (stripos($this->agent, 'Mobile') !== false) {
                    $this->setMobile();
                } elseif (stripos($this->agent, 'Android') !== false) {
                    $this->setTablet();
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the browser is a PlayStation
     * @return bool True if the browser is PlayStation otherwise false
     */
    protected function checkBrowserPlayStation(): bool
    {
        if (stripos($this->agent, 'PlayStation ') !== false) {
            $aresult = explode(' ', stristr($this->agent, 'PlayStation '));
            $this->setBrowser(self::BROWSER_PLAYSTATION);
            if (isset($aresult[0])) {
                $aversion = explode(')', $aresult[2]);
                $this->setVersion($aversion[0]);
                if (stripos($this->agent, 'Portable)') !== false || stripos($this->agent, 'Vita') !== false) {
                    $this->setMobile();
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Determine the user's platform (last updated 2.0)
     * @return void
     */
    protected function checkPlatform()
    {
        if (stripos($this->agent, 'windows') !== false) {
            $this->platform = self::PLATFORM_WINDOWS;
        } else {
            if (stripos($this->agent, 'iPad') !== false) {
                $this->platform = self::PLATFORM_IPAD;
            } else {
                if (stripos($this->agent, 'iPod') !== false) {
                    $this->platform = self::PLATFORM_IPOD;
                } else {
                    if (stripos($this->agent, 'iPhone') !== false) {
                        $this->platform = self::PLATFORM_IPHONE;
                    } elseif (stripos($this->agent, 'mac') !== false) {
                        $this->platform = self::PLATFORM_APPLE;
                    } elseif (stripos($this->agent, 'android') !== false) {
                        $this->platform = self::PLATFORM_ANDROID;
                    } elseif (stripos($this->agent, 'Silk') !== false) {
                        $this->platform = self::PLATFORM_FIRE_OS;
                    } elseif (stripos($this->agent, 'linux') !== false && stripos(
                        $this->agent,
                        'SMART-TV'
                    ) !== false) {
                        $this->platform = self::PLATFORM_LINUX . '/' . self::PLATFORM_SMART_TV;
                    } elseif (stripos($this->agent, 'linux') !== false) {
                        $this->platform = self::PLATFORM_LINUX;
                    } else {
                        if (stripos($this->agent, 'Nokia') !== false) {
                            $this->platform = self::PLATFORM_NOKIA;
                        } else {
                            if (stripos($this->agent, 'BlackBerry') !== false) {
                                $this->platform = self::PLATFORM_BLACKBERRY;
                            } elseif (stripos($this->agent, 'FreeBSD') !== false) {
                                $this->platform = self::PLATFORM_FREEBSD;
                            } elseif (stripos($this->agent, 'OpenBSD') !== false) {
                                $this->platform = self::PLATFORM_OPENBSD;
                            } elseif (stripos($this->agent, 'NetBSD') !== false) {
                                $this->platform = self::PLATFORM_NETBSD;
                            } elseif (stripos($this->agent, 'OpenSolaris') !== false) {
                                $this->platform = self::PLATFORM_OPENSOLARIS;
                            } elseif (stripos($this->agent, 'SunOS') !== false) {
                                $this->platform = self::PLATFORM_SUNOS;
                            } elseif (stripos($this->agent, 'OS\/2') !== false) {
                                $this->platform = self::PLATFORM_OS2;
                            } elseif (stripos($this->agent, 'BeOS') !== false) {
                                $this->platform = self::PLATFORM_BEOS;
                            } elseif (stripos($this->agent, 'win') !== false) {
                                $this->platform = self::PLATFORM_WINDOWS;
                            } elseif (stripos($this->agent, 'Playstation') !== false) {
                                $this->platform = self::PLATFORM_PLAYSTATION;
                            } elseif (stripos($this->agent, 'Roku') !== false) {
                                $this->platform = self::PLATFORM_ROKU;
                            } elseif (stripos($this->agent, 'iOS') !== false) {
                                $this->platform = self::PLATFORM_IPHONE . '/' . self::PLATFORM_IPAD;
                            } elseif (stripos($this->agent, 'tvOS') !== false) {
                                $this->platform = self::PLATFORM_APPLE_TV;
                            } elseif (stripos($this->agent, 'curl') !== false) {
                                $this->platform = self::PLATFORM_TERMINAL;
                            } elseif (stripos($this->agent, 'CrOS') !== false) {
                                $this->platform = self::PLATFORM_CHROME_OS;
                            } elseif (stripos($this->agent, 'okhttp') !== false) {
                                $this->platform = self::PLATFORM_JAVA_ANDROID;
                            } elseif (stripos($this->agent, 'PostmanRuntime') !== false) {
                                $this->platform = self::PLATFORM_POSTMAN;
                            } elseif (stripos($this->agent, 'Iframely') !== false) {
                                $this->platform = self::PLATFORM_I_FRAME;
                            }
                        }
                    }
                }
            }
        }
    }
}
