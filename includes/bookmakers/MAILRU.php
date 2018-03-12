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

class MAILRU extends BasicOperations {
    public function __construct($tab_num = 1)
    {
        $this->BK_ID = 'MAILRU';
        $this->tab_num = $tab_num;

        list($this->login, $this->pass) = explode(":", $this->getLoginData());
    }

    public function doAuth() {
        waitForLoad();
    }

    public function isSignedIn() {}
}
