<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Scriptixru\SypexGeo;
use Redis;
/*Контроллер ведения статистики в Redis*/

class StatController extends Controller {

    private $redis;

    public function __construct() {
        /*Подключение к REDIS*/
        $this->redis = app()->make('redis');
    }
    public function showStat($page, Request $request)
    {
        $menu = '<a href="/admin/allsite" ' . ($page=='allsite' ? ' style="font-weight:bold"' : '') . '>Весь сайт</a> ';
        for ($i = 0; $i < 12; $i++) {
            $menu .= '<a href="/admin/page' . $i . '"' . ($page == 'page'.$i ? ' style="font-weight:bold"' : '') . '>Page' . ($i + 1) . '</a> ';
        }
        $content = '';
        if( $page!='allsite' ) $page = '/site/'.$page;
        $prms = array('browser' => '1. Браузеры', 'os' => '2. ОСи', 'city' => '3. ГЕО (город)', 'ref' => '4. Рефы');
        $types = array('visit' => '// Хиты', 'visitip' => '//Уник. IP', 'visitcookie' => '//Уник. куки');
        foreach ($prms as $param => $title) {
            $content .= '<div class="block"><h2>'.$title.'</h2>';
            foreach ($types as $type => $title) {
                $content .= '<h4>'.$title.'</h4>';
                if($stats = $this->redis->get($type.':'.$page.':'.$param.'s'))
                {
                    $vals = explode("|", $stats);
                    foreach ($vals as $value) {
                        $content .= $value.': '.$this->redis->get($type.':'.$page.':'.$param.':'.$value).'<br/>';                    
                    }
                }else $content .= 'Нет данных';
            }
            $content .= '</div>';
        }
        
        $response = view('admin', ['menu' => $menu, 'content' => $content]);
        return $response;
    }

    public function statRecord(Request $request, $page, $cookie_exist, $cookie_exist_page) {
        $resultIP = 'Ваш IP: ' . $request->ip();

        $user_agent = $_SERVER["HTTP_USER_AGENT"];
        
        /*Определение Браузера*/
        if (strpos($user_agent, "Firefox") !== false)
            $browser = "Firefox";
        elseif (strpos($user_agent, "Opera") !== false)
            $browser = "Opera";
        elseif (strpos($user_agent, "OPR") !== false)
            $browser = "NEW Opera";
        elseif (strpos($user_agent, "Iron") !== false)
            $browser = "SRWare Iron";
        elseif (strpos($user_agent, "MSIE") !== false)
            $browser = "Internet Explorer";
        elseif (strpos($user_agent, "Edge") !== false)
            $browser = "Microsoft Edge";
        elseif (strpos($user_agent, "Safari") !== false)
            $browser = "Safari";
        elseif (strpos($user_agent, "Chrome") !== false)
            $browser = "Chrome";
        else
            $browser = "Неизвестный";
        //$resultIP .= "<br/>USERAGENT: $user_agent ";
        $resultIP .= "<br/>Ваш браузер: $browser ";
        
        /*Определение ОС*/
        $os = $this->getOS($user_agent);
        $resultIP .= "<br/>Ваша ОС: " . $os;
        $ip = $request->ip();
        $ip = '232.223.11.11';
        
        /*Определение города по IP, сторонняя библиотека SypexGeo*/
        $city_arr = \SypexGeo::get($ip);
        $city = $city_arr['city']['name_ru'];
        $resultIP .= "<br/>Ваш Город: " . $city_arr['city']['name_ru'];
        
        /*Определение рефа*/
        $request1 = $refstring = '';
        if (isset($_SERVER['HTTP_REFERER']))
            $request1 = urldecode($_SERVER['HTTP_REFERER']);
        if (!preg_match('/(' . str_replace('.', '\.', $_SERVER['SERVER_NAME']) . ')/', $request1) && $request1) {
            if ($array = parse_url($request1))
                $refstring = $array['host'];
        }
        $resultIP .= "<br/>РЕФ: " . $refstring.' | '.$request1;
        
        /* allsite - параметр всего сайта*/

        /* Запись статистики хитов */        
        $stats = array('browser' => $browser, 'os' => $os, 'city' => $city, 'ref' => $refstring);
        foreach ($stats as $key => $value) {
            $this->visitPage('visit', 'allsite', $key, $value);
            $this->visitPage('visit', $page, $key, $value);
        }
        
        /* Запись статистики по уник. IP */
        if (!$this->redis->get('ip:allsite:' . $ip)) {
            $this->redis->set('ip:allsite:' . $ip, 1);
            foreach ($stats as $key => $value) {
                $this->visitPage('visitip', 'allsite', $key, $value);
            }
        }
        if (!$this->redis->get('ip:' . $page . ':' . $ip)) {
            $this->redis->set('ip:' . $page . ':' . $ip, 1);
            foreach ($stats as $key => $value) {
                $this->visitPage('visitip', $page, $key, $value);
            }
        }
        
        /* Запись статистики по уник. куки */
        if( !$cookie_exist ) {
            foreach ($stats as $key => $value) {
                $this->visitPage('visitcookie', 'allsite', $key, $value);
            }
        }
        if( !$cookie_exist_page ) {
            foreach ($stats as $key => $value) {
                $this->visitPage('visitcookie', $page, $key, $value);
            }
        }
        return $resultIP;
    }

    /*Запись статистики по параметрам*/
    //$type - тип посещения (visit - хит, visitip - уник.ip, visitcookie - уник.cookie и т.д.)
    //$page - страница посещения
    //$param - параметр посещения (os, browser, city, ref и т.д.)
    //$value - значение параметра посещения    
    private function visitPage($type, $page, $param, $value) {
        if ($visit = $this->redis->get($type.':' . $page . ':' . $param . ':' . $value)) {
            $this->redis->set($type.':' . $page . ':' . $param . ':' . $value, ++$visit);
        } else
        {
            //запись новых значений параметров посещения в список сначений
            if($values = $this->redis->get($type.':' . $page . ':' . $param . 's'))
            {
                $this->redis->set($type.':' . $page . ':' . $param . 's', $values.'|'.$value);
            }
            else $this->redis->set($type.':' . $page . ':' . $param . 's', $value);
            
            $this->redis->set($type.':' . $page . ':' . $param . ':' . $value, 1);
        }
    }

    /*Определение ОС*/
    public function getOS($userAgent) {
        $oses = array(
                // Mircrosoft Windows Operating Systems
                'Windows 3.11' => '(Win16)',
                'Windows 95' => '(Windows 95)|(Win95)|(Windows_95)',
                'Windows 98' => '(Windows 98)|(Win98)',
                'Windows 2000' => '(Windows NT 5.0)|(Windows 2000)',
                'Windows 2000 Service Pack 1' => '(Windows NT 5.01)',
                'Windows XP' => '(Windows NT 5.1)|(Windows XP)',
                'Windows Server 2003' => '(Windows NT 5.2)',
                'Windows Vista' => '(Windows NT 6.0)|(Windows Vista)',
                'Windows 7' => '(Windows NT 6.1)|(Windows 7)',
                'Windows 8' => '(Windows NT 6.3)|(Windows NT 6.2)|(Windows 8)',
                'Windows 10' => '(Windows NT 10)',
                'Windows NT 4.0' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
                'Windows ME' => '(Windows ME)|(Windows 98; Win 9x 4.90 )',
                'Windows CE' => '(Windows CE)',
// UNIX Like Operating Systems
                'Mac OS X Kodiak (beta)' => '(Mac OS X beta)',
                'Mac OS X Cheetah' => '(Mac OS X 10.0)',
                'Mac OS X Puma' => '(Mac OS X 10.1)',
                'Mac OS X Jaguar' => '(Mac OS X 10.2)',
                'Mac OS X Panther' => '(Mac OS X 10.3)',
                'Mac OS X Tiger' => '(Mac OS X 10.4)',
                'Mac OS X Leopard' => '(Mac OS X 10.5)',
                'Mac OS X Snow Leopard' => '(Mac OS X 10.6)',
                'Mac OS X Lion' => '(Mac OS X 10.7)',
                'Mac OS X' => '(Mac OS X)',
                'Mac OS' => '(Mac_PowerPC)|(PowerPC)|(Macintosh)',
                'Open BSD' => '(OpenBSD)',
                'SunOS' => '(SunOS)',
                'Solaris 11' => '(Solaris/11)|(Solaris11)',
                'Solaris 10' => '((Solaris/10)|(Solaris10))',
                'Solaris 9' => '((Solaris/9)|(Solaris9))',
                'CentOS' => '(CentOS)',
                'QNX' => '(QNX)',
// Kernels
                'UNIX' => '(UNIX)',
// Linux Operating Systems
                'Ubuntu 12.10' => '(Ubuntu/12.10)|(Ubuntu 12.10)',
                'Ubuntu 12.04 LTS' => '(Ubuntu/12.04)|(Ubuntu 12.04)',
                'Ubuntu 11.10' => '(Ubuntu/11.10)|(Ubuntu 11.10)',
                'Ubuntu 11.04' => '(Ubuntu/11.04)|(Ubuntu 11.04)',
                'Ubuntu 10.10' => '(Ubuntu/10.10)|(Ubuntu 10.10)',
                'Ubuntu 10.04 LTS' => '(Ubuntu/10.04)|(Ubuntu 10.04)',
                'Ubuntu 9.10' => '(Ubuntu/9.10)|(Ubuntu 9.10)',
                'Ubuntu 9.04' => '(Ubuntu/9.04)|(Ubuntu 9.04)',
                'Ubuntu 8.10' => '(Ubuntu/8.10)|(Ubuntu 8.10)',
                'Ubuntu 8.04 LTS' => '(Ubuntu/8.04)|(Ubuntu 8.04)',
                'Ubuntu 6.06 LTS' => '(Ubuntu/6.06)|(Ubuntu 6.06)',
                'Red Hat Linux' => '(Red Hat)',
                'Red Hat Enterprise Linux' => '(Red Hat Enterprise)',
                'Fedora 17' => '(Fedora/17)|(Fedora 17)',
                'Fedora 16' => '(Fedora/16)|(Fedora 16)',
                'Fedora 15' => '(Fedora/15)|(Fedora 15)',
                'Fedora 14' => '(Fedora/14)|(Fedora 14)',
                'Chromium OS' => '(ChromiumOS)',
                'Google Chrome OS' => '(ChromeOS)',
// Kernel
                'Linux' => '(Linux)|(X11)',
// BSD Operating Systems
                'OpenBSD' => '(OpenBSD)',
                'FreeBSD' => '(FreeBSD)',
                'NetBSD' => '(NetBSD)',
// Mobile Devices
                'Android' => '(Android)',
                'iPod' => '(iPod)',
                'iPhone' => '(iPhone)',
                'iPad' => '(iPad)',
//DEC Operating Systems
                'OS/8' => '(OS/8)|(OS8)',
                'Older DEC OS' => '(DEC)|(RSTS)|(RSTS/E)',
                'WPS-8' => '(WPS-8)|(WPS8)',
// BeOS Like Operating Systems
                'BeOS' => '(BeOS)|(BeOS r5)',
                'BeIA' => '(BeIA)',
// OS/2 Operating Systems
                'OS/2 2.0' => '(OS/220)|(OS/2 2.0)',
                'OS/2' => '(OS/2)|(OS2)',
// Search engines
                'Search engine or robot' => '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp)|(msnbot)|(Ask Jeeves/Teoma)|(ia_archiver)'
        );

        foreach ($oses as $os => $pattern) {
            if (preg_match("/$pattern/i", $userAgent)) {
                return $os;
            }
        }
        return 'Unknown';
    }

}
