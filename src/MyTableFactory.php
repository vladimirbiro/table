<?php
namespace VB\Table;

use Nette\Application\UI\Control;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Tracy\Debugger;

class MyTableFactory extends Control
{
	/** @var Session */
	private $session;

	/** @var SessionSection */
	private $sessionSection;

	const ACTIONS = ['view', 'edit', 'delete'];
	const TYPES = ['text', 'link', 'boolean', 'datetime', 'price'];

	private $data;
	private $dataSource;
	private $columns = [];
	private $types = [];
	private $colGroup = [];
	private $actions = [];
	private $custom = [];
	private $styles = [];
	private $ajax = false;
	private $paginationPosition = null;
	private $pages;
	private $rowsOnPage = 20;
	private $actualPage = 1;
	private $prefix;
	private $timeFormat = [];
	private $dataRenderer = [];
	private $emptyMessage = null;
	private $customDelete = false;
	private $adminLock = [];

	private $iconBooleans;
	private $iconPagerArrows;
	private $iconDateTime;
	private $iconView;
	private $iconEdit;
	private $iconDelete;

	private $key;

	public $onLink;
	public $onView;
	public $onEdit;
	public $onDelete;
	public $onRePage;


	/**
	 * MyTableFactory constructor.
	 * @param Session $session
	 */
	public function __construct(Session $session)
	{
		$this->session = $session;
	}


	/**
	 * Render
	 */
	public function render()
	{
		if (!$this->prefix) {
			$this->error('Nastavte prefix tabuľky');
		}

		if (!$this->data) {
			$this->error('Žiadne dáta v tabluľke');
		}

		$this->mergeData();

		$this->template->dataSource = $this->dataSource;
		$this->template->columns = ArrayHash::from($this->columns);
		$this->template->custom = $this->custom ? $this->custom : null;
		$this->template->types = $this->types;
		$this->template->colGroup = $this->colGroup;
		$this->template->actions = $this->actions;
		$this->template->styles = $this->styles;
		$this->template->ajax = $this->ajax;
		$this->template->prefix = $this->prefix;
		$this->template->timeFormat = $this->timeFormat;
		$this->template->adminLock = $this->adminLock;

		$this->template->paginationPosition = $this->paginationPosition;
		$this->template->pages = $this->pages;
		$this->template->page = $this->getActualPage();
		$this->template->round = 15;

		$this->template->iconBooleans = $this->iconBooleans;
		$this->template->iconPagerArrows = $this->iconPagerArrows;
		$this->template->iconDateTime = $this->iconDateTime;
		$this->template->iconView = $this->iconView;
		$this->template->iconEdit = $this->iconEdit;
		$this->template->iconDelete = $this->iconDelete;

		$this->template->emptyMessage = $this->emptyMessage;


		$this->template->render(__DIR__ . '/template.latte');
	}


	/**
	 * Set Prefix
	 * @param $prefix
	 * @return $this
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = Strings::lower($prefix);
		$this->sessionSection = $this->session->getSection(($this->prefix ? $this->prefix . '-' : '') . 'tablebox');

		return $this;
	}


	/**
	 * Data Source
	 * @param null $data
	 * @return $this
	 */
	public function setDataSource($data = null)
	{
		if ($data) {
			$this->data = $data;
			$this->pages = ceil(count($data) / $this->rowsOnPage);
		}

		return $this;
	}


	/**
	 * @param $key
	 * @param $column
	 * @return $this
	 */
	public function addColumnText($key, $column)
	{
		$this->addType($key, $column, 'text');

		return $this;
	}


	/**
	 * Add Link
	 * @param $key
	 * @param $column
	 * @return $this
	 */
	public function addColumnLink($key, $column)
	{
		$this->addType($key, $column, 'link');

		return $this;
	}


	/**
	 * Add Boolean
	 * @param $key
	 * @param $column
	 * @return $this
	 */
	public function addColumnBoolean($key, $column)
	{
		$this->addType($key, $column, 'boolean');

		return $this;
	}


	/**
	 * Add Date Time
	 * @param $key
	 * @param string $column
	 * @param string $format
	 * @return $this
	 */
	public function addColumnDateTime($key, $column, $format = 'd.m.Y H:i')
	{
		$this->timeFormat[$key] = $format;
		$this->addType($key, $column, 'datetime');

		return $this;
	}


	/**
	 * Add Price
	 * @param $key
	 * @param $column
	 * @return $this
	 */
	public function addColumnPrice($key, $column)
	{
		$this->addType($key, $column, 'price');

		return $this;
	}


	/**
	 * Add Type
	 * @param $key
	 * @param $column
	 * @param $type
	 */
	private function addType($key, $column, $type)
	{
		$this->columns[$key] = $column;
		$this->types[$key] = (in_array($type, self::TYPES))
			? $type
			: 'text';

		$this->key = $key;
	}


	/**
	 * Add Style
	 * @param $key
	 * @param $style
	 * @return $this
	 */
	public function addStyle($key, $style)
	{
		$this->styles[$key] = $style;
		return $this;
	}


	/**
	 * Set Col Group
	 * @param $key
	 * @param $colGroup
	 * @return $this
	 */
	public function setColGroup($key, $colGroup)
	{
		$this->colGroup[$key] = $colGroup;

		return $this;
	}


	/**
	 * Actions (Buttons)
	 * @param $key
	 * @param null $name
	 * @param bool $allow
	 * @return $this
	 */
	public function addAction($key, $name = null)
	{
		if (in_array($key, self::ACTIONS)) {
			$this->actions[$key] = $name;
		}

		return $this;
	}


	/**
	 * Set Ajax
	 * @param bool $s
	 * @return $this
	 */
	public function setAjax($s = true)
	{
		$this->ajax = $s;

		return $this;
	}


	/**
	 * Set Icon Booleans
	 * @param $true
	 * @param $false
	 * @return $this
	 */
	public function setIconBooleans($true, $false)
	{
		$this->iconBooleans = [
			0 => $false,
			1 => $true
		];

		return $this;
	}


	/**
	 * Set Icon Pages Arrows
	 * @param $left
	 * @param $right
	 * @return $this
	 */
	public function setIconPagerArrows($left, $right)
	{
		$this->iconPagerArrows = [
			'left' => $left,
			'right' => $right
		];

		return $this;
	}


	/**
	 * Set Icon DateTime
	 * @param $icon
	 * @return $this
	 */
	public function setIconDateTime($icon)
	{
		$this->iconDateTime = $icon;

		return $this;
	}


	/**
	 * Set Icon Delete
	 * @param $icon
	 * @return $this
	 */
	public function setIconView($icon)
	{
		$this->iconView = $icon;

		return $this;
	}


	/**
	 * Set Icon Delete
	 * @param $icon
	 * @return $this
	 */
	public function setIconEdit($icon)
	{
		$this->iconEdit = $icon;

		return $this;
	}


	/**
	 * Set Icon Delete
	 * @param $icon
	 * @return $this
	 */
	public function setIconDelete($icon)
	{
		$this->iconDelete = $icon;

		return $this;
	}


	/**
	 * Set Pagination Position
	 * @param $position
	 * @return $this
	 */
	public function setPaginationPosition($position)
	{
		$this->paginationPosition = $position;

		return $this;
	}


	/**
	 * Set Rows On Page
	 * @param $rowsOnPage
	 * @return $this
	 */
	public function setRowsOnPage($rowsOnPage)
	{
		$this->rowsOnPage = $rowsOnPage;

		return $this;
	}


	/**
	 * Set Actual Page
	 * @param $actualPage
	 * @return $this
	 */
	public function setActualPage($actualPage)
	{
		$this->actualPage = $actualPage;
		$this->sessionSection['page'] = $this->actualPage;
		return $this;
	}


	/**
	 * Get Actual Page
	 * @return int|mixed
	 */
	public function getActualPage()
	{
		return $this->sessionSection['page'] ?? 1;
	}


	/**
	 * Handle Link
	 * @param $id
	 */
	public function handleLink($id)
	{
		$this->onLink((int) $id);
	}


	/**
	 * Handle View
	 * @param $id
	 */
	public function handleView($id)
	{
		$this->onView((int) $id);
	}


	/**
	 * Handle Edit
	 * @param $id
	 */
	public function handleEdit($id)
	{
		$this->onEdit((int) $id);
	}


	/**
	 * Handle Delete
	 * @param $id
	 */
	public function handleDelete($id)
	{
		if (isset($this->adminLock[$id]) && $this->adminLock[$id] === true) {
			return;
		}

		unset($this->data[$id]);

		$item = $this->data->get($id);
		$this->onDelete($item);

		if ($this->customDelete !== true) {
			$item->delete();
		}

		if ($this->getPresenter()->isAjax()) {
			$this->redrawControl('tablebox');
		}
	}


	/**
	 * Handle RePage
	 * @param $p
	 */
	public function handleRePage(int $p)
	{
		$this->setActualPage($p);
		$this->redrawControl('tablebox');
	}


	/**
	 * @param callable $renderer
	 * @return $this
	 */
	public function setRenderer(callable $renderer)
	{
		foreach ($this->data as $id => $item) {
			$this->dataRenderer[$id][$this->key] = $renderer($item);
		}

		return $this;
	}


	/**
	 * Set - Empty Message
	 * @param $message
	 * @return $this
	 */
	public function setEmptyMessage($message)
	{
		$this->emptyMessage = $message;

		return $this;
	}


	/**
	 * @param callable $renderer
	 * @return $this
	 */
	public function setAdminLock(callable $renderer)
	{
		foreach ($this->data as $id => $item) {
			if ($renderer($item) === true) {
				$this->adminLock[$id] = true;
			}
		}

		return $this;
	}


	/**
	 * Set - Custom Delete
	 * @param $state
	 * @return $this
	 */
	public function setCustomDelete($state)
	{
		$this->customDelete = $state;

		return $this;
	}


	private function mergeData()
	{
		if ($this->getActualPage() > $this->pages) {
			$this->setActualPage(1);
		}

		$this->dataSource = $this->data->limit($this->rowsOnPage, ($this->rowsOnPage*$this->getActualPage()) - $this->rowsOnPage);

		$row = [];
		foreach ($this->data as $key => $item) {
			$row[$key] = $item->toArray();

			if (isset($this->dataRenderer[$key])) {
				foreach ($this->dataRenderer[$key] as $k => $i) {
					$row[$key][$k] = $i;
				}
			}
		}

		$this->dataSource = ArrayHash::from($row);
	}
}
