<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\StatController;

/*Контроллер вывода страниц сайта*/
class SiteController extends Controller {


    public function __construct() {
    }

    public function page($id, Request $request) {
        /*Вывод меню страниц*/
        $menu = '';
        for ($i = 0; $i < 12; $i++) {
            $menu .= '<a href="/site/page' . $i . '"' . ($id == $i ? ' style="font-weight:bold"' : '') . '>Page' . ($i + 1) . '</a> ';
        }
        $page = parse_url($request->url(), PHP_URL_PATH);
        
        /*Проверка куки*/
        $cookie_exist = false;
        if ($request->cookie('visitCookie')) {
            $cookie_exist = true;
        }
        $cookie_exist_page = false;
        if ($request->cookie('visitCookie'.$page)) {
            $cookie_exist_page = true;
        }
        /*Вывод контента и запись статистики*/
        $stat = new StatController();
        $content = $stat->statRecord($request, $page, $cookie_exist, $cookie_exist_page);
        
        $response = response(view('page', ['menu' => $menu, 'content' => $content]));
        if (!$cookie_exist)
            $response->withCookie(cookie('visitCookie', '1', 1440));
        if (!$cookie_exist_page)
            $response->withCookie(cookie('visitCookie'.$page, '1', 1440));
        return $response;
    }
}
