<?php

namespace Flowpack\Listable\Fusion\Eel\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the Flow package "Flowpack.Listable".           *
 *                                                                        */

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/**
 * Sort nodes by their position in the node tree. Please use with care, as this can become a very expensive
 * operation, if you operate on bigger subtrees.
 *
 * Use it like this:
 *
 *    ${q(node).children().sortRecursiveByIndex(['ASC'|'DESC'])}
 */
class SortRecursiveByIndexOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    protected static $shortName = 'sortRecursiveByIndex';

    /**
     * {@inheritdoc}
     */
    protected static $priority = 100;

    #[\Neos\Flow\Annotations\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * {@inheritdoc}
     *
     * We can only handle CR Nodes.
     */
    public function canEvaluate($context)
    {
        return (isset($context[0]) && ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $sortOrder = 'ASC';
        if (!empty($arguments[0]) && in_array($arguments[0], ['ASC', 'DESC'], true)) {
            $sortOrder = $arguments[0];
        }

        $nodes = $flowQuery->getContext();
        if (count($nodes) <= 1) {
            return;
        }

        $pathMap = [];
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($nodes[0]);

        // Collect all NodeAggregateId paths
        /** @var Node $node */
        foreach ($nodes as $node) {
            $nodeIdentifier = $node->aggregateId->value;
            $ancestors = $subgraph->findAncestorNodes($node->aggregateId, FindAncestorNodesFilter::create());
            $pathMap[$nodeIdentifier] = array_merge([$node->aggregateId], array_map(static fn($ancestor) => $ancestor->aggregateId, iterator_to_array($ancestors)));
        }

        $flip = $sortOrder === 'DESC' ? -1 : 1;

        usort($nodes, function (Node $a, Node $b) use ($subgraph, $pathMap, $flip) {
            // Both nodes are equal
            if ($a->equals($b)) {
                return 0;
            }

            $commonParentPathSegmentNodeAggregateId = null;
            $childNodesCache = [];

            // Compare path starting from the site root until a difference is found.
            $aPath = $pathMap[$a->aggregateId->value];
            $bPath = $pathMap[$b->aggregateId->value];
            while (count($aPath) > 0 && count($bPath) > 0) {

                /** @var NodeAggregateId $aPathSegmentNodeAggregateId */
                $aPathSegmentNodeAggregateId = array_pop($aPath);
                /** @var NodeAggregateId $bPathSegmentNodeAggregateId */
                $bPathSegmentNodeAggregateId = array_pop($bPath);

                $pathDiff = (!$aPathSegmentNodeAggregateId->equals($bPathSegmentNodeAggregateId));

                if ($pathDiff === true) {
                    // Path is different at this segment, so we need to figure out their position under the last common parent.
                    if ($commonParentPathSegmentNodeAggregateId === null) {
                        return 0;
                    }

                    if (!isset($childNodesCache[$commonParentPathSegmentNodeAggregateId->value])) {
                        $childNodesCache[$commonParentPathSegmentNodeAggregateId->value] = $subgraph->findChildNodes($commonParentPathSegmentNodeAggregateId, FindChildNodesFilter::create());
                    }

                    $positionDiff = $this->getIndexOfNodeAggregateIdInNodes($childNodesCache[$commonParentPathSegmentNodeAggregateId->value], $aPathSegmentNodeAggregateId)
                        - $this->getIndexOfNodeAggregateIdInNodes($childNodesCache[$commonParentPathSegmentNodeAggregateId->value], $bPathSegmentNodeAggregateId);
                    return $flip * $positionDiff < 0 ? -1 : 1;
                }
                // No diff in path, we need to go deeper, or they are eventually equal
                $commonParentPathSegmentNodeAggregateId = $aPathSegmentNodeAggregateId;
            }

            return 0;
        });

        $flowQuery->setContext($nodes);
    }

    private function getIndexOfNodeAggregateIdInNodes(Nodes $childNodes, NodeAggregateId $nodeAggregateId): int
    {
        foreach ($childNodes as $key => $childNode) {
            if ($childNode->aggregateId->value === $nodeAggregateId->value) {
                return $key;
            }
        }

        throw new \Exception("Exception on sorting nodes by there position in content tree.");
    }
}
