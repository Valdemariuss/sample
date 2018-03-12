<?php
include(__DIR__ . "/wd_run.php");

define('YANDEX_LOGIN_DATA', 'reme3d2y@yandex.ru:testtesttest');

/*
    Нужно реализовать следующие методы для класса:

    1. doAuth()
    2. isSignedIn()
    3. getMessages() - метод, возращающий последние 30 входящих сообщений в виде json с полями: from, subject, date
    4. doStrangeThing() - нужно открыть панель поиска, кликнуть по первому результату и вернуть кол-во найденных писем.
*/


$BK = new YANDEX();
// $BK->getMessages();
$BK->doStrangeThing();