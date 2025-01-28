<?php

namespace Flowpack\Listable\Fusion\Eel\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the Flow package "Flowpack.Listable".           *
 *                                                                        */

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;

/**
 * FlowQuery operation to filter Nodes by a date property
 */
class FilterByDateOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    protected static $shortName = 'filterByDate';

    /**
     * {@inheritdoc}
     */
    protected static $priority = 100;

    /**
     * {@inheritdoc}
     *
     * We can only handle CR Nodes.
     */
    public function canEvaluate($context)
    {
        return (!isset($context[0]) || ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * @param array $arguments The arguments for this operation.
     *                         First argument is property to filter by, must be DateTime.
     *                         Second is Date operand, must be DateTime object.
     *                         And third is a compare operator: '<' or '>', '>' by default
     *
     * @return void
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (empty($arguments[0])) {
            throw new FlowQueryException('filterByDate() needs property name by which nodes should be filtered', 1332492263);
        }
        if (empty($arguments[1])) {
            throw new FlowQueryException('filterByDate() needs date value by which nodes should be filtered', 1332493263);
        }

        /** @var \DateTime $date */
        list($filterByPropertyPath, $date) = $arguments;
        $compareOperator = '>';
        if (!empty($arguments[2]) && in_array($arguments[2], ['<', '>'], true)) {
            $compareOperator = $arguments[2];
        }

        $filteredNodes = [];
        foreach ($flowQuery->getContext() as $node) {
            /** @var Node $node */
            $propertyValue = $node->getProperty($filterByPropertyPath);
            if (($compareOperator === '>' && $propertyValue > $date) || ($compareOperator === '<' && $propertyValue < $date)) {
                $filteredNodes[] = $node;
            }
        }

        $flowQuery->setContext($filteredNodes);
    }
}
