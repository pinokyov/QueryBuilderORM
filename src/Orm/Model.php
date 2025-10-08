<?php

namespace App\Orm;

use App\Orm\Relations\HasMany;
use App\Orm\Relations\BelongsTo;
use App\Orm\Relations\Relation;
use JsonSerializable;
use RuntimeException;

abstract class Model implements JsonSerializable
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected array $attributes = [];
    protected array $original = [];
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        
        // Initialize the table name if not set
        $this->getTable();
    }

    public function __get(string $key)
    {
        // First, try to get the attribute directly
        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttribute($key);
        }
        
        // Check if it's a relationship method
        if (method_exists($this, $key)) {
            // Get the relationship
            $relation = $this->$key();
            
            // If it's a relationship, return the results
            if (method_exists($relation, 'getResults')) {
                return $relation->getResults();
            }
            
            return $relation;
        }
        
        // Check if it's a dynamic property that should be accessible
        if (property_exists($this, $key)) {
            return $this->$key;
        }
        
        return null;
    }

    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    public function getAttribute(string $key)
    {
        if (!array_key_exists($key, $this->attributes)) {
            // Check for relationship
            if (method_exists($this, $key)) {
                return $this->getRelationValue($key);
            }
            
            return null;
        }
        
        $value = $this->attributes[$key];
        
        // If the attribute is in the casts array, cast it
        if ($this->hasCast($key)) {
            $value = $this->castAttribute($key, $value);
            
            // For datetime/date fields, format them as strings when accessed
            $castType = $this->casts[$key];
            if (in_array($castType, ['datetime', 'date']) && $value instanceof \DateTimeInterface) {
                return $value->format($castType === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s');
            }
            
            return $value;
        }
        
        return $value;
    }

    public function setAttribute(string $key, $value): self
    {
        if ($this->isFillable($key)) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    public function isFillable(string $key): bool
    {
        // If fillable is empty, all attributes are fillable
        if (empty($this->fillable)) {
            return true;
        }
        
        // Check if the key is in the fillable array
        return in_array($key, $this->fillable);
    }

    public function hasCast(string $key): bool
    {
        return array_key_exists($key, $this->casts);
    }

    protected function castAttribute(string $key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        $type = $this->casts[$key];

        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'datetime':
            case 'date':
                return $this->asDateTime($value);
            default:
                return $value;
        }
    }
    
    /**
     * Return a timestamp as DateTime object.
     */
    protected function asDateTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }
        
        // If the value is numeric, we'll assume it's a UNIX timestamp
        if (is_numeric($value)) {
            return (new \DateTime())->setTimestamp($value);
        }
        
        // If the value is in YYYY-MM-DD format, we'll assume it's a date
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value)) {
            return \DateTime::createFromFormat('Y-m-d', $value);
        }
        
        // Finally, we'll just assume it's a date string and try to parse it
        return new \DateTime($value);
    }

    protected function getRelationValue(string $key)
    {
        if (method_exists($this, $key)) {
            $relation = $this->$key();

            if ($relation instanceof Relation) {
                return $relation->getResults();
            }
        }

        return null;
    }
    
    /**
     * Define a one-to-many relationship.
     */
    protected function hasMany(string $related, string $foreignKey = null, string $localKey = 'id')
    {
        $instance = new $related;
        $foreignKey = $foreignKey ?: $this->getForeignKey() . '_id';
        
        return new HasMany($this, $instance, $foreignKey, $localKey);
    }
    
    /**
     * Define an inverse one-to-one or many relationship.
     */
    protected function belongsTo(string $related, string $foreignKey = null, string $ownerKey = 'id', string $relation = null)
    {
        $instance = new $related;
        $foreignKey = $foreignKey ?: $instance->getForeignKey() . '_id';
        
        return new BelongsTo($this, $instance, $foreignKey, $ownerKey, $relation);
    }
    
    /**
     * Get the foreign key for the model.
     */
    public function getForeignKey(): string
    {
        return strtolower(class_basename($this));
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }
    
    public function getTable(): string
    {
        if (!isset($this->table)) {
            $className = get_class($this);
            $baseName = str_replace('App\\Models\\', '', $className);
            $this->table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName)) . 's';
        }

        return $this->table;
    }
    
    /**
     * @deprecated Use getTable() instead
     */
    protected function getTableName(): string
    {
        return $this->getTable();
    }

    public static function query(): QueryBuilder
    {
        $instance = new static();
        return new QueryBuilder($instance->getTable(), get_class($instance));
    }

    public static function all(): array
    {
        $instance = new static();
        $items = $instance->query()->get();
        
        // If we already have model instances, return them as is
        if (!empty($items) && $items[0] instanceof static) {
            return $items;
        }
        
        // Otherwise, convert the results to model instances
        return array_map(function ($item) use ($instance) {
            if ($item instanceof static) {
                return $item;
            }
            return $instance->newFromBuilder((array) $item);
        }, $items);
    }

    public static function find($id): ?self
    {
        $instance = new static();
        $result = $instance->query()->where($instance->getKeyName(), '=', $id)->first();
        
        if (!$result) {
            return null;
        }
        
        // If we already have a model instance, return it as is
        if ($result instanceof static) {
            return $result;
        }
        
        // Otherwise, create a new model instance from the result
        return $instance->newFromBuilder((array) $result);
    }

    protected function newFromBuilder(array $attributes): self
    {
        $model = new static();
        $model->exists = true;
        $model->original = $attributes;
        
        // Fill the model with attributes and cast them
        foreach ($attributes as $key => $value) {
            $model->setAttribute($key, $value);
        }
        
        // Set the timestamps if they exist
        if (isset($attributes['created_at'])) {
            $model->created_at = $attributes['created_at'];
        }
        if (isset($attributes['updated_at'])) {
            $model->updated_at = $attributes['updated_at'];
        }
        
        return $model;
    }

    public function save(): bool
    {
        $this->updateTimestamps();
        
        if ($this->exists) {
            return $this->performUpdate();
        }
        
        return $this->performInsert();
    }

    protected function performInsert(): bool
    {
        $attributes = $this->getAttributesForSave();
        
        if (empty($attributes)) {
            return true;
        }
        
        $query = $this->query();
        $result = $query->insert($attributes);
        
        if ($result) {
            $this->exists = true;
            $this->original = $attributes;
            
            if (empty($this->attributes[$this->primaryKey])) {
                $this->attributes[$this->primaryKey] = Database::lastInsertId();
            }
        }
        
        return $result;
    }

    protected function performUpdate(): bool
    {
        $attributes = $this->getAttributesForSave();
        
        if (empty($attributes)) {
            return true;
        }
        
        $query = $this->query()
            ->where($this->primaryKey, '=', $this->getKey());
        
        $result = $query->update($attributes);
        
        if ($result) {
            $this->original = array_merge($this->original, $attributes);
        }
        
        return $result;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return true;
        }
        
        $query = $this->query()->where($this->primaryKey, '=', $this->getKey());
        $deleted = $query->delete();
        
        if ($deleted) {
            $this->exists = false;
        }
        
        return $deleted;
    }

    public function getKey()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    protected function getAttributesForSave(): array
    {
        $attributes = $this->attributes;
        
        if ($this->timestamps) {
            $this->updateTimestamps();
            
            // Ensure timestamps are in the attributes
            $attributes['created_at'] = $this->attributes['created_at'] ?? null;
            $attributes['updated_at'] = $this->attributes['updated_at'] ?? null;
        }
        
        // Remove primary key if it's auto-increment and not set
        if (empty($this->attributes[$this->primaryKey])) {
            unset($attributes[$this->primaryKey]);
        }
        
        return $attributes;
    }

    protected function updateTimestamps(): void
    {
        $time = new \DateTime();
        
        if (!$this->exists && !$this->isDirty('created_at')) {
            $this->attributes['created_at'] = $time;
        }
        
        $this->attributes['updated_at'] = $time;
    }

    public function isDirty($attributes = null): bool
    {
        if ($attributes === null) {
            return $this->attributes !== $this->original;
        }
        
        if (is_string($attributes)) {
            $attributes = [$attributes];
        }
        
        foreach ($attributes as $attribute) {
            if (!array_key_exists($attribute, $this->attributes) || 
                !array_key_exists($attribute, $this->original) ||
                $this->attributes[$attribute] !== $this->original[$attribute]) {
                return true;
            }
        }
        
        return false;
    }

    public function toArray(): array
    {
        $attributes = $this->attributes;
        
        foreach ($this->hidden as $hidden) {
            unset($attributes[$hidden]);
        }
        
        return $attributes;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
