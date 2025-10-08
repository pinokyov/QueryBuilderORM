<?php

namespace App\Orm;

use App\Orm\Relations\HasMany;
use App\Orm\Relations\BelongsTo;
use PDO;
use PDOStatement;

class QueryBuilder
{
    protected string $table;
    protected array $wheres = [];
    protected array $selects = ['*'];
    protected array $joins = [];
    protected array $orderBy = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $bindings = [];
    protected array $with = [];
    protected string $modelClass;

    public function __construct(string $table, string $modelClass = null)
    {
        $this->table = $table;
        $this->modelClass = $modelClass ?: 'App\\Models\\' . ucfirst(rtrim($table, 's'));
    }

    public function select(array|string $columns = ['*']): self
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where(string $column, string $operator = null, $value = null, string $boolean = 'AND'): self
    {
        if (is_null($value)) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];

        return $this;
    }

    public function orWhere(string $column, string $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtolower($direction) === 'asc' ? 'ASC' : 'DESC'
        ];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];

        return $this;
    }

    public function with($relations): self
    {
        $this->with = is_array($relations) ? $relations : func_get_args();
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSql();
        $statement = $this->runQuery($sql, $this->bindings);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert rows to model instances
        $results = [];
        foreach ($rows as $row) {
            $model = new $this->modelClass();
            
            // Ensure the ID is set as an attribute
            if (isset($row['id'])) {
                $model->id = $row['id'];
            }
            
            $model->fill($row);
            $model->exists = true;
            $results[] = $model;
        }

        // Eager load relationships if any
        if (!empty($this->with) && !empty($results)) {
            $this->eagerLoadRelations($results);
        }

        return $results;
    }

    protected function eagerLoadRelations(&$models): void
    {
        if (empty($models)) {
            return;
        }
        
        // Get the first model to check for the relationship methods
        $firstModel = $models[0];
        
        // If models are objects, get their class, otherwise use the model class from constructor
        if (is_object($firstModel)) {
            $modelClass = get_class($firstModel);
            $model = $firstModel;
        } else {
            $modelClass = $this->modelClass;
            $model = new $modelClass();
        }
        
        foreach ($this->with as $relation) {
            if (method_exists($model, $relation)) {
                $this->loadRelation($models, $model, $relation);
            }
        }
    }

    protected function loadRelation(&$models, $model, $relation): void
    {
        $relationResults = $model->$relation();
        
        if ($relationResults instanceof HasMany) {
            $this->loadHasMany($models, $relationResults, $relation);
        } elseif ($relationResults instanceof BelongsTo) {
            $this->loadBelongsTo($models, $relationResults, $relation);
        }
    }

    protected function loadHasMany(&$models, $relation, $relationName): void
    {
        // Skip if no models to process
        if (empty($models)) {
            return;
        }
        
        // Get the IDs of all parent models
        $ids = [];
        $modelMap = [];
                
        foreach ($models as $i => $model) {
            if (is_object($model)) {
                $id = $model->id ?? null;
            } else {
                $id = $model['id'] ?? null;
            }
            
            if ($id !== null) {
                $ids[] = $id;
                $modelMap[$id] = $model;
            }
        }
        
        
        
        // Get the related model instance to access its table and primary key
        $relatedModel = $relation->getRelated();
        $foreignKey = $relation->getForeignKey();
        
        // Get all related models
        $related = $relatedModel->query()
            ->whereIn($foreignKey, $ids)
            ->get();
            
        $sql = $relatedModel->query()
            ->whereIn($foreignKey, $ids)
            ->toSql();
        
        // Group related models by the foreign key
        $grouped = [];
        foreach ($related as $item) {
            $key = $item->{$foreignKey};
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $item;
        }
        
        // Set the related models on each parent model
        foreach ($modelMap as $id => $model) {
            $relatedModels = $grouped[$id] ?? [];
            
            // Set the related models as a property on the model
            if (is_object($model)) {
                // First try to set the property directly
                if (property_exists($model, $relationName)) {
                    $model->$relationName = $relatedModels;
                } else {
                    // If the property doesn't exist, try using reflection
                    try {
                        $reflection = new \ReflectionClass($model);
                        if ($reflection->hasProperty($relationName)) {
                            $property = $reflection->getProperty($relationName);
                            $property->setAccessible(true);
                            $property->setValue($model, $relatedModels);
                        } else {
                            // If the property doesn't exist, create a dynamic property
                            $model->$relationName = $relatedModels;
                        }
                    } catch (\ReflectionException $e) {
                        // If reflection fails, fall back to dynamic property
                        $model->$relationName = $relatedModels;
                    }
                }
                
                // Also set the relation as loaded to prevent lazy loading
                if (method_exists($model, 'setRelation')) {
                    $model->setRelation($relationName, $relatedModels);
                }
            } elseif (is_array($model)) {
                $models[array_search($model, $models, true)][$relationName] = $relatedModels;
            }
        }
    }

    protected function loadBelongsTo(&$models, $relation, $relationName): void
    {
        $ids = [];
        foreach ($models as $model) {
            $id = is_object($model) ? $model->{$relation->getForeignKey()} : $model[$relation->getForeignKey()];
            if ($id) {
                $ids[] = $id;
            }
        }

        if (empty($ids)) {
            return;
        }

        $related = $relation->getRelated()::query()
            ->whereIn('id', array_unique($ids))
            ->get();

        $keyed = [];
        foreach ($related as $item) {
            $keyed[$item->id] = $item;
        }

        foreach ($models as $model) {
            $id = is_object($model) ? $model->{$relation->getForeignKey()} : $model[$relation->getForeignKey()];
            if (isset($keyed[$id])) {
                $model->$relationName = $keyed[$id];
            }
        }
    }

    public function first()
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }
    
    public function count(): int
    {
        // Save the current selects
        $originalSelects = $this->selects;
        
        // Set to count query
        $this->selects = ['COUNT(*) as count'];
        
        // Get the count
        $result = $this->runQuery($this->toSql(), $this->bindings)->fetch(PDO::FETCH_ASSOC);
        
        // Restore original selects
        $this->selects = $originalSelects;
        
        return (int) ($result['count'] ?? 0);
    }

    public function insert(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        return $this->runQuery($sql, $data)->rowCount() > 0;
    }

    public function update(array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $set);
        
        $sql = "UPDATE {$this->table} SET {$setClause}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }
        
        $bindings = array_merge($data, $this->bindings);
        
        return $this->runQuery($sql, $bindings)->rowCount() > 0;
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }
        
        return $this->runQuery($sql, $this->bindings)->rowCount() > 0;
    }

    public function toSql(): string
    {
        $select = implode(', ', $this->selects);
        $sql = "SELECT {$select} FROM {$this->table}";
        
        // Add joins
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }
        
        // Add where conditions
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }
        
        // Add order by
        if (!empty($this->orderBy)) {
            $orderBy = [];
            foreach ($this->orderBy as $order) {
                $orderBy[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        }
        
        // Add limit and offset
        if (!is_null($this->limit)) {
            $sql .= " LIMIT {$this->limit}";
            
            if (!is_null($this->offset)) {
                $sql .= " OFFSET {$this->offset}";
            }
        }
        
        return $sql;
    }

    protected function compileWheres(): string
    {
        $whereClauses = [];
        
        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : $where['boolean'] . ' ';
            
            if ($where['type'] === 'basic') {
                $parameter = 'where_' . str_replace('.', '_', $where['column']) . '_' . count($this->bindings);
                $whereClauses[] = $boolean . $where['column'] . ' ' . $where['operator'] . ' :' . $parameter;
                $this->bindings[$parameter] = $where['value'];
            } elseif ($where['type'] === 'in') {
                $parameters = [];
                foreach ($where['values'] as $i => $value) {
                    $param = 'where_in_' . str_replace('.', '_', $where['column']) . '_' . $i;
                    $parameters[] = ':' . $param;
                    $this->bindings[$param] = $value;
                }
                $not = $where['not'] ? 'NOT ' : '';
                $whereClauses[] = $boolean . $where['column'] . ' ' . $not . 'IN (' . implode(', ', $parameters) . ')';
            }
        }
        
        return implode(' ', $whereClauses);
    }
    
    protected function runQuery(string $sql, array $bindings = []): PDOStatement
    {
        $statement = Database::query($sql, $bindings);
        $this->bindings = []; // Reset bindings after query execution
        return $statement;
    }
}
