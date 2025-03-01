<?php

declare(strict_types=1);

namespace Whirlwind\ElasticSearch\Repository\TableGateway;

use Whirlwind\Infrastructure\Repository\Exception\DeleteException;
use Whirlwind\Infrastructure\Repository\Exception\UpdateException;
use Whirlwind\ElasticSearch\Persistence\Query\QueryFactory;
use Whirlwind\ElasticSearch\Persistence\ConditionBuilder\ConditionBuilder;
use Whirlwind\ElasticSearch\Persistence\Connection;
use Whirlwind\ElasticSearch\Persistence\Query\Query;
use Whirlwind\Infrastructure\Repository\TableGateway\TableGatewayInterface;

class ElasticTableGateway implements TableGatewayInterface
{
    protected Connection $connection;

    protected QueryFactory $queryFactory;

    protected ConditionBuilder $conditionBuilder;

    protected string $collectionName;

    protected string $documentType;

    public function __construct(
        Connection $connection,
        QueryFactory $queryFactory,
        ConditionBuilder $conditionBuilder,
        string $collectionName,
        string $documentType
    ) {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
        $this->conditionBuilder = $conditionBuilder;
        $this->collectionName = $collectionName;
        $this->documentType = $documentType;
    }

    public function queryOne(array $conditions, array $relations = []): ?array
    {
        $conditions = $this->conditionBuilder->build($conditions);
        $query = $this->queryFactory->create($this->connection);
        $query
            ->from($this->collectionName)
            ->where($conditions);
        $result = $query->one();
        return $result ?: null;
    }

    public function insert(array $data, array $options = []): ?array
    {
        $connection = $this->connection;

        if (\array_key_exists('_id', $data)) {
            $id = $data['_id'];
            unset($data['_id']);
        }
        $result = $connection->createCommand()->insert(
            $this->collectionName,
            $this->documentType,
            $data,
            $id ?? null,
            $options
        );
        return ['_id' => $result['_id']];
    }

    public function updateOne(array $data, array $options = []): void
    {
        if (empty($data['_id'])) {
            throw new UpdateException($data, "Primary key _id not provided");
        }
        $key = $data['_id'];
        unset($data['_id']);
        $this->connection->createCommand()->update(
            $this->collectionName,
            $this->documentType,
            $key,
            $data,
            $options
        );
    }

    public function updateAll(array $data, array $conditions): int
    {
        throw new \RuntimeException('Not implemented');
    }

    public function deleteOne(array $data, array $options = []): void
    {
        if (empty($data['_id'])) {
            throw new DeleteException($data, "Primary key _id not provided");
        }
        $this->connection->createCommand()->delete(
            $this->collectionName,
            $this->documentType,
            $data['_id'],
            $options
        );
    }

    public function deleteAll(array $conditions): int
    {
        $result = $this->connection->createCommand(
            $this->collectionName,
            $this->documentType,
            [
                'query' => $this->conditionBuilder->build($conditions)
            ]
        )
            ->deleteByQuery();

        return \is_array($result) ? $result['total'] : 0;
    }

    public function queryAll(
        array $conditions,
        array $order = [],
        int $limit = 0,
        int $offset = 0,
        array $relations = []
    ): array {
        $conditions = $this->conditionBuilder->build($conditions);
        /** @var Query $query */
        $query = $this->queryFactory->create($this->connection);
        $query
            ->from($this->collectionName)
            ->where($conditions);

        if ($limit > 0) {
            $query->limit($limit)->offset($offset);
        }

        if (!empty($order)) {
            $query->orderBy($order);
        }

        return $query->all();
    }

    public function aggregate($column, $operator, array $conditions): string
    {
        throw new \RuntimeException('Not implemented');
    }

    public function aggregateCount(string $field = '', array $conditions = []): string
    {
        $conditions = $this->conditionBuilder->build($conditions);
        /** @var Query $query */
        $query = $this->queryFactory->create($this->connection);
        return (string)$query->from($this->collectionName)->where($conditions)->count('*');
    }

    public function aggregateSum(string $field, array $conditions = []): string
    {
        throw new \RuntimeException('Not implemented');
    }

    public function aggregateAverage(string $field, array $conditions = []): string
    {
        throw new \RuntimeException('Not implemented');
    }

    public function aggregateMin(string $field, array $conditions = []): string
    {
        throw new \RuntimeException('Not implemented');
    }

    public function aggregateMax(string $field, array $conditions = []): string
    {
        throw new \RuntimeException('Not implemented');
    }
}
