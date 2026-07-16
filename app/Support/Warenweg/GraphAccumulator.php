<?php

namespace App\Support\Warenweg;

/**
 * Collects Cytoscape nodes and edges for a Warenweg trace, de-duplicating by id
 * and marking the anchor. Kept separate from the page so the per-entity graph
 * builders stay small and readable.
 */
class GraphAccumulator
{
    /** @var array<string, array{data: array<string, mixed>}> */
    protected array $nodes = [];

    /** @var array<string, array{data: array<string, mixed>}> */
    protected array $edges = [];

    public function __construct(protected ?string $anchorId = null) {}

    /**
     * Add a node once. Later calls for the same id are ignored, so the first
     * (richest) definition wins.
     *
     * @param  array<string, mixed>  $data
     */
    public function node(string $id, array $data): void
    {
        if (isset($this->nodes[$id])) {
            return;
        }

        $data['id'] = $id;
        $data['matched'] = $id === $this->anchorId;
        $this->nodes[$id] = ['data' => $data];
    }

    public function edge(string $source, string $target, bool $gap = false): void
    {
        $id = "{$source}__{$target}";
        $this->edges[$id] ??= ['data' => ['id' => $id, 'source' => $source, 'target' => $target, 'gap' => $gap]];
    }

    public function has(string $id): bool
    {
        return isset($this->nodes[$id]);
    }

    public function isEmpty(): bool
    {
        return $this->nodes === [];
    }

    /**
     * @return array{nodes: list<array{data: array<string, mixed>}>, edges: list<array{data: array<string, mixed>}>, anchor: string|null}
     */
    public function result(): array
    {
        return [
            'nodes' => array_values($this->nodes),
            'edges' => array_values($this->edges),
            'anchor' => $this->anchorId,
        ];
    }
}
