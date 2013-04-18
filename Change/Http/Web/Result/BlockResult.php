<?php
namespace Change\Http\Web\Result;

/**
 * @name \Change\Http\Web\Result\BlockResult
 */
class BlockResult
{
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $etag;

	/**
	 * @var array
	 */
	protected $head = array();

	/**
	 * @var string
	 */
	protected $html = false;

	/**
	 * @var \Callable
	 */
	protected $htmlCallback;

	/**
	 * @param string $id
	 * @param string $name
	 */
	function __construct($id, $name)
	{
		$this->id = $id;
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @param string $etag
	 */
	public function setEtag($etag)
	{
		$this->etag = $etag;
	}

	/**
	 * @return string
	 */
	public function getEtag()
	{
		return $this->etag;
	}

	/**
	 * @param array $head
	 */
	public function setHead($head)
	{
		$this->head = $head;
	}

	/**
	 * @return array
	 */
	public function getHead()
	{
		return $this->head;
	}

	/**
	 * @param string $headString
	 * @return $this
	 */
	public function addHeadAsString($headString)
	{
		$this->head[] = $headString;
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $headString
	 * @return $this
	 */
	public function addNamedHeadAsString($name, $headString)
	{
		$this->head[$name] = $headString;
		return $this;
	}

	/**
	 * @param array $heads
	 */
	public function addHeads(array $heads)
	{
		foreach ($heads as $key => $head)
		{
			if (is_int($key))
			{
				$this->addHeadAsString($head);
			}
			else
			{
				$this->addNamedHeadAsString($key, $head);
			}
		}
	}

	/**
	 * @param string $html
	 */
	public function setHtml($html)
	{
		$this->html = $html;
	}

	/**
	 * @return string
	 */
	public function getHtml()
	{
		if ($this->html === false)
		{
			$callable = $this->getHtmlCallback();
			if (is_callable($callable))
			{
				$this->html = call_user_func($callable);
			}
			else
			{
				throw new \RuntimeException('Unable to getHTML', 999999);
			}
		}
		return $this->html;
	}

	/**
	 * @param \Callable $htmlCallback
	 */
	public function setHtmlCallback($htmlCallback)
	{
		$this->htmlCallback = $htmlCallback;
	}

	/**
	 * @return \Callable
	 */
	public function getHtmlCallback()
	{
		return $this->htmlCallback;
	}
}