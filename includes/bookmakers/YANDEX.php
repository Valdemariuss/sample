<?php
require_once(dirname(__FILE__) . "/BasicOperations.php");

/***

WD() - глобальная ф-ия возвращающая экземпляр WebDriver для работы с селениумом (https://github.com/daluu/php-webdriver-bindings)

Наследуемые от BasicOperations методы:

    checkActiveTab     - Метот для переключения на рабочую($tab_num) вкладку
    navigate           - Метод для перехода на указанный урл
    waitForLoad        - Метод для ожидания загрузки страницы
    js                 - Метод для выполнения js-кода
    waitFor            - Метод для многократного выполнения кода до получения нужного результата

***/

class YANDEX extends BasicOperations {

    public function __construct($tab_num = 1)
    {
        $this->BK_ID = 'YANDEX';
        $this->tab_num = $tab_num;

        list($this->login, $this->pass) = explode(":", $this->getLoginData());
    }

    public function doAuth() {
        $this->navigate('https://yandex.ru/');
        $this->waitForLoad();
        $this->js("$('input[name=login]').val('".$this->login."');");
        $this->js("$('input[name=passwd]').val('".$this->pass."');");
        $this->js("$('input[name=login]').parents('form:eq(0)').submit();");
        echo "[+] authorization complete \n";
    }

    public function isSignedIn() {
        $this->navigate('https://yandex.ru/');
        $this->waitForLoad();
        $is_signed_in = WD()->execute("return ( $('input[name=login]').get(0) ? false : true );");
        if($is_signed_in){
            echo "[+] isSignedIn yes\n";    
        } else {
            echo "[+] isSignedIn no\n";   
        }
        return $is_signed_in;

    }

    public function getMessages() {
        $messages = array();
        $this->doAuth();
        $this->navigate('https://mail.yandex.ru/lite/');
        $this->waitForLoad();
        $links = WD()->execute("
            var links = [];
            $('a.b-messages__from:lt(2)').each(function(){
               links.push($(this).attr('href')); 
            });
            return links;
        ");
        foreach ($links as &$link) {
             array_push($messages, $this->getMessage($link));
        }
        return $messages;
    }

    public function getMessage($url) {
        $url = "https://mail.yandex.ru".$url;
        $this->navigate($url);
        $this->waitForLoad(); 
        $mes = WD()->execute(" return $('.b-message-body__content').text().replace(/\s+/g, ' '); ");
        echo "[+] get message from ".$url."\n";
        return $mes;
    }

    public function doStrangeThing() {        
        $this->doAuth();
        $this->navigate('https://mail.yandex.ru/');
        $this->waitForLoad();
        WD()->execute("$('.textinput__control').click();");      
        sleep(2);
        $search = WD()->execute("return $('.mail-SearchSuggest > .menu__item:eq(0)').attr('title');");
        echo "[+] search word - ".$search."\n";        
        $count = $this->getSearchCount('https://mail.yandex.ru/lite/search?request='.$search);
        echo "[+] search results count - ".$count."\n";
        return $count;
    }

    public function getSearchCount($url) {
        $count = 0;            
        $this->navigate($url);
        $this->waitForLoad();
        $count = WD()->execute("return $('.b-messages > .b-messages__message').size();");
        $next = WD()->execute("return $('.b-pager__next').attr('href');");        
        if($next && count($next)) {
            echo "[+] search results next - ".$next."\n";
            $count = $count + $this->getSearchCount("https://mail.yandex.ru".$next);    
        }
        return $count;
    }
}
