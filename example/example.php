<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/MyTableFactory.php';
require_once __DIR__ . '/TableFactory.php';

$requestFactory = new Nette\Http\RequestFactory;
$request = $requestFactory->createHttpRequest();
$response = new Nette\Http\Response;
$session = new Nette\Http\Session($request, $response);

$latte = new Latte\Engine;
$latte->setTempDirectory(__DIR__ . '/temp');


echo 'Table example';

$tableFactory = new \Factory\TableFactory($session);
$c = $tableFactory->create();

$admins = [
	[
		'is_active' => 1,
		'username' => 'Jan Hrach',
		'time_add' => new DateTime(),
		'time_edit' => new DateTime(),
	]
];

$c->setDataSource($admins)

	// ... Nastavenie prefixu
	->setPrefix('Example')

	// ... Culomns
	->addColumnBoolean('is_active', 'Stav')
	->addColumnLink('username', 'Meno')
	->addColumnDateTime('time_add', 'Dátum pridania')
	->addColumnDateTime('time_edit', 'Dátum zmeny')

	// ... Styles
	->addStyle('is_active', 'text-center')
	->addStyle('actions', 'text-center')
	->addStyle('time_add', 'text-center')
	->addStyle('time_edit', 'text-center')

	// ... ColGroup
	->setColGroup('is_active', 'small-min')
	->setColGroup('time_add', 'small-20')
	->setColGroup('time_edit', 'small-20')
	->setColGroup('actions', 'small-10')

	// ... Active
	->addAction('edit')
	->addAction('delete')

;

$c->render();