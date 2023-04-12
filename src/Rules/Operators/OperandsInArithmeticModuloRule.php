<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp\Mod as AssignOpMod;
use PhpParser\Node\Expr\BinaryOp\Mod as BinaryOpMod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class OperandsInArithmeticModuloRule implements Rule
{

	/** @var OperatorRuleHelper */
	private $helper;

	/** @var bool */
	private $bleedingEdge;

	public function __construct(OperatorRuleHelper $helper, bool $bleedingEdge = false)
	{
		$this->helper = $helper;
		$this->bleedingEdge = $bleedingEdge;
	}

	public function getNodeType(): string
	{
		return Expr::class;
	}

	/**
	 * @return string[] errors
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof BinaryOpMod) {
			$left = $node->left;
			$right = $node->right;
		} elseif ($node instanceof AssignOpMod && $this->bleedingEdge) {
			$left = $node->var;
			$right = $node->expr;
		} else {
			return [];
		}

		$messages = [];
		$leftType = $scope->getType($left);
		if (!$this->helper->isValidForArithmeticOperation($scope, $left)) {
			$messages[] = sprintf(
				'Only numeric types are allowed in %%, %s given on the left side.',
				$leftType->describe(VerbosityLevel::typeOnly())
			);
		}

		$rightType = $scope->getType($right);
		if (!$this->helper->isValidForArithmeticOperation($scope, $right)) {
			$messages[] = sprintf(
				'Only numeric types are allowed in %%, %s given on the right side.',
				$rightType->describe(VerbosityLevel::typeOnly())
			);
		}

		return $messages;
	}

}
