<?php

// use composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// set default timezone (PHP 5.4)
date_default_timezone_set('UTC');

// 1. Create new calendar
$versionDate = date('YmdHis');
$vCalendar = new \Eluceo\iCal\Component\Calendar('-//Kotaro Terada//WCA Competitions Calendar ' . $versionDate . '//EN');
$vCalendar->setName('WCA Competitions');

// WCAデータベースから大会リストを取得
require_once __DIR__ . '/config.php';
$comps = array();
try {
    $dbh = new PDO('mysql:host=' . MYSQL_HOST . ';dbname=' . MYSQL_DATABASE, MYSQL_USERNAME, MYSQL_PASSWORD);
    foreach($dbh->query('SELECT cmp.`id`, cmp.`name`, `cityName`, cnt.`name` AS `countryName`, cmp.`year`, cmp.`month`, cmp.`day`, cmp.`endMonth`, cmp.`endDay`
                         FROM `Competitions` AS cmp LEFT JOIN `Countries` AS cnt ON cmp.`CountryId` = cnt.`id`') as $row) {
        $comps[$row['id']] = $row;
    }
    $dbh = null;
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}

// 2. Create events
foreach ($comps as $id => $row) {
    $vEvent = new \Eluceo\iCal\Component\Event();

    // 大会名、場所、URL
    $vEvent->setSummary($row['name']);
    $vEvent->setLocation($row['cityName'] . ', ' . $row['countryName']);
    $vEvent->setDescription('https://www.worldcubeassociation.org/results/c.php?i=' . $row['id']);

    // 開催日
    // Note: 終了日は+1日して設定するのが通例ぽい？
    $vEvent->setDtStart(new \DateTime(date('Y-m-d', mktime(0, 0, 0, (int)$row['month'], (int)$row['day'], (int)$row['year']))));
    $vEvent->setDtEnd(new \DateTime(date('Y-m-d', mktime(0, 0, 0, (int)$row['endMonth'], (int)$row['endDay'] + 1, (int)$row['year']))));
    $vEvent->setNoTime(true);

    // Adding Timezone (optional)
    $vEvent->setUseTimezone(true);

    // 3. Add event to calendar
    $vCalendar->addComponent($vEvent);
}

// 4. Set headers
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="cal.ics"');

// 5. Output
echo $vCalendar->render();
