# goryashchie-tury-reklama
Автоматизация контекстной рекламы для турагентств

Скрипт ads.php создаёт десятки тысяч объявлений по низко частотным запросам (низкий уровень конкуренции). В качестве ключевых слов используются названия отелей (включая синонимы), которые получены с сайтов различных туроператоров: Тез Тур, Библио Глобус, Туи, Джоин Ап, Ростинг, АэроБелСервис, Интерсити и многих других. В настройках скрипта вы можете настроить таргетинг по нужным вам направлениям (Турция, Египет...) и городам вылетов (Минск, Киев...). Есть все страны и города вылетов (РБ, РФ, РК, Украина).

Текст объявлений также настраивается. Шаблон: Горящие туры в отель X, где X это один из вариантов написания отеля из настроенной в таргетинг страны. В адрес целевой страницы передаются такие параметры как название отеля, категория отеля (количество звезд), страна, город вылета. Вести можно на лэндинг с учетом передаваемых параметров.

На выходе скрипт генерирует 4 csv таблицы, которые последовательно импортируются в кабинете Google Ads. Пример готовых таблиц можно посмотреть в папке results. В примере настроен следующий таргетинг: Горящие туры в отели Турции и Египта с вылетом из Минска. В качестве рекламируемого сайта в примере используется сайт https://горящиетуры.com/

Адрес сайта, таргетинг и другие настройки редактируются в файле ads.php
