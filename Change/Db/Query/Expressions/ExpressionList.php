<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Column
 */
class ExpressionList extends \Change\Db\Query\Expressions\AbstractExpression implements \Countable
{
	/** 
	 * @var \Change\Db\Query\Expressions\AbstractExpression[]
	 */
	protected $list;
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression[] $list
	 */
	public function __construct($list = array())
	{
		$this->setList($list);
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\AbstractExpression[]
	 */
	public function getList()
	{
		return $this->list;
	}

	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression[] $list
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public function setList($list = array())
	{
		if (!is_array($list))
		{
			throw new \InvalidArgumentException('Argument 1 must be a Array');
		}
		$this->list = array_map(function (AbstractExpression $item) {return $item;}, $list);
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\AbstractExpression $expression
	 * @return \Change\Db\Query\Expressions\ExpressionList
	 */
	public function add(AbstractExpression $expression)
	{
		$this->list[] = $expression;
		return $this;
	}
	
	/**
	 * @return integer
	 */
	public function count()
	{
		return count($this->list);
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return implode(', ', array_map(function (AbstractExpression $item) {
			return $item->toSQL92String();
		}, $this->getList()));
	}
}