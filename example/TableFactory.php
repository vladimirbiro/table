<?php
namespace Factory;

use VB\Table\MyTableFactory;
use Nette\Http\Session;

class TableFactory
{
	/** @var Session */
	private $session;


	public function __construct(Session $session)
	{
		$this->session = $session;
	}


	public function create()
	{
		$t = new MyTableFactory($this->session);
		$t
			->setIconBooleans('q', 's')
			->setIconPagerArrows('c', 'f')
			->setIconDateTime('g')
			->setIconView('n')
			->setIconEdit('o')
			->setIconDelete('m')
			->setPaginationPosition('bottom')
			->setAjax();
		return $t;
	}
}