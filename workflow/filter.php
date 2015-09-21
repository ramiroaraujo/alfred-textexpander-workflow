<?php
require_once __DIR__ . "/vendor/autoload.php";

use Alfred\Workflow;
use Cake\Collection\Collection as C;
use CFPropertyList\CFPropertyList;

$w = new Workflow();
$query = isset($argv[1]) ? trim($argv[1]) : false;

//glob all textexpander files named group_...
$snippets = (new C(glob(getenv('HOME') . '/Library/Application Support/TextExpander/Settings.textexpandersettings/group*.xml')))
    //parse each plist
    ->map(function ($file) {
        return (new CFPropertyList($file))->toArray()['snippetPlists'];
    })
    //flatten result
    ->unfold()
    //filter out invalid snippets
    ->filter(function ($s) {
        return strlen($s['abbreviation']) > 0;
    })
    //if filter text provided, filter by it
    ->filter(function ($s) use ($query) {
        if (!$query) return true;

        return (new C(['abbreviation', 'label', 'plainText']))
            ->some(function ($k) use ($s, $query) { return mb_stripos($s[$k], $query) !== false; });
    });

if ($snippets->isEmpty()) {
    //404 if collection is empty
    $results = [[
        'title' => '404 Not Found',
        'icon'  => false,
        'valid' => 'yes',
    ]];
} else {
    //build results array
    $results = $snippets->map(function ($s) {
        return [
            'uid'      => $s['uuidString'],
            'arg'      => $s['abbreviation'],
            'title'    => $s['label'] ? $s['label'] : $s['abbreviation'],
            'subtitle' => $s['abbreviation'],
            'icon'     => false,
            'valid'    => 'yes',
        ];
    })->toList();
}

//set results
$w->results = $results;

//return alfred's xml
echo $w->toXML();


