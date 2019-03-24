<?php

declare(strict_types=1);

namespace MsgPhp\Domain\Tests;

use MsgPhp\Domain\DomainCollection;
use MsgPhp\Domain\DomainId;
use MsgPhp\Domain\Exception\DuplicateEntityException;
use MsgPhp\Domain\Exception\EntityNotFoundException;
use MsgPhp\Domain\Exception\InvalidClassException;
use MsgPhp\Domain\Tests\Fixtures\Entities;
use MsgPhp\Domain\Tests\Fixtures\TestDomainEntityRepository;
use MsgPhp\Domain\Tests\Fixtures\TestDomainId;
use PHPUnit\Framework\TestCase;

abstract class DomainEntityRepositoryTestCase extends TestCase
{
    protected static $entityTypes = [
        Entities\TestEntity::class,
        Entities\TestPrimitiveEntity::class,
        Entities\TestCompositeEntity::class,
        Entities\TestDerivedEntity::class,
        Entities\TestDerivedCompositeEntity::class,
        Entities\TestParentEntity::class,
        Entities\TestChildEntity::class,
    ];

    protected static $supportsAutoGeneratedIds = true;

    public function testFindAll(): void
    {
        $repository = static::createRepository(Entities\TestPrimitiveEntity::class);
        $entities = [
            $entity1 = Entities\TestPrimitiveEntity::create(['id' => new TestDomainId('1')]),
            $entity2 = Entities\TestPrimitiveEntity::create(['id' => new TestDomainId('2')]),
            $entity3 = Entities\TestPrimitiveEntity::create(['id' => new TestDomainId('3')]),
        ];

        $this->assertEntityCollectionEquals([], $repository->doFindAll());
        $this->assertEntityCollectionEquals([], $repository->doFindAll(1));
        $this->assertEntityCollectionEquals([], $repository->doFindAll(1, 1));
        $this->assertEntityCollectionEquals([], $repository->doFindAll(1, 0));
        $this->assertEntityCollectionEquals([], $repository->doFindAll(0, 10));
        $this->assertEntityCollectionEquals([], $repository->doFindAll(10, 10));

        static::flushEntities($entities);

        $this->assertEntityCollectionEquals($entities, $repository->doFindAll());
        $this->assertEntityCollectionEquals([$entity2, $entity3], $repository->doFindAll(1));
        $this->assertEntityCollectionEquals([$entity2], $repository->doFindAll(1, 1));
        $this->assertEntityCollectionEquals([$entity2, $entity3], $repository->doFindAll(1, 0));
        $this->assertEntityCollectionEquals($entities, $repository->doFindAll(0, 10));
        $this->assertEntityCollectionEquals([], $repository->doFindAll(10, 10));
        $this->assertEntityCollectionEquals([$entity1, $entity2], $repository->doFindAll(0, 2));
    }

    public function testFindAllByFields(): void
    {
        $repository = static::createRepository(Entities\TestCompositeEntity::class);
        $repository2 = static::createRepository(Entities\TestEntity::class);
        $entities = [
            $entity1 = Entities\TestCompositeEntity::create(['idA' => new TestDomainId('1'), 'idB' => 'foo']),
            $entity2 = Entities\TestCompositeEntity::create(['idA' => new TestDomainId('2'), 'idB' => 'foo']),
            $entity3 = Entities\TestCompositeEntity::create(['idA' => new TestDomainId('3'), 'idB' => 'bar']),
            $entity4 = Entities\TestEntity::create(['id' => new TestDomainId('1'), 'intField' => 1, 'boolField' => false]),
        ];

        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => 1]));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => '2']));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => new TestDomainId()], 1));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => [null, 'foo', new TestDomainId('2'), new TestDomainId('3')]], 1, 1));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => [new TestDomainId('2'), new TestDomainId('1'), new TestDomainId()]], 1, 0));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => [new TestDomainId('1'), new TestDomainId('3')]], 0, 10));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idB' => 'foo'], 0, 10));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idB' => 'bar'], 10, 10));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idB' => 'foo'], 0, 2));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idB' => 'foo']));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => [1, '2'], 'idB' => 'foo']));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => [1, '2'], 'idB' => 'foo'], 1));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['strField' => null]));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['strField' => '']));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['strField' => 'foo']));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['strField' => [null, 'foo']]));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['intField' => null]));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['intField' => '1']));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['intField' => [1, 2]]));

        static::flushEntities($entities);

        $this->assertEntityCollectionEquals([$entity1], $repository->doFindAllByFields(['idA' => 1]));
        $this->assertEntityCollectionEquals([$entity2], $repository->doFindAllByFields(['idA' => '2']));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => new TestDomainId()], 1));
        $this->assertEntityCollectionEquals([$entity3], $repository->doFindAllByFields(['idA' => [new TestDomainId('2'), new TestDomainId('3')]], 1, 1));
        $this->assertEntityCollectionEquals([$entity2], $repository->doFindAllByFields(['idA' => [new TestDomainId('2'), new TestDomainId('1'), new TestDomainId()]], 1, 0));
        $this->assertEntityCollectionEquals([$entity1, $entity3], $repository->doFindAllByFields(['idA' => [new TestDomainId('1'), new TestDomainId('3')]], 0, 10));
        $this->assertEntityCollectionEquals([$entity3], $repository->doFindAllByFields(['idA' => new TestDomainId('3')], 0, 10));
        $this->assertEntityCollectionEquals([], $repository->doFindAllByFields(['idA' => new TestDomainId('2')], 10, 10));
        $this->assertEntityCollectionEquals([$entity1], $repository->doFindAllByFields(['idA' => new TestDomainId('1')], 0, 2));
        $this->assertEntityCollectionEquals([$entity1, $entity2], $repository->doFindAllByFields(['idB' => 'foo']));
        $this->assertEntityCollectionEquals([$entity1], $repository->doFindAllByFields(['idB' => 'foo'], 0, 1));
        $this->assertEntityCollectionEquals([$entity2], $repository->doFindAllByFields(['idB' => 'foo'], 1, 1));
        $this->assertEntityCollectionEquals([$entity1, $entity2], $repository->doFindAllByFields(['idA' => [1, '2'], 'idB' => 'foo']));
        $this->assertEntityCollectionEquals([$entity2], $repository->doFindAllByFields(['idA' => [1, '2'], 'idB' => 'foo'], 1));
        $this->assertEntityCollectionEquals([$entity4], $repository2->doFindAllByFields(['strField' => null]));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['strField' => '']));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['strField' => 'foo']));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['strField' => [null, 'foo']]));
        $this->assertEntityCollectionEquals([], $repository2->doFindAllByFields(['intField' => null]));
        $this->assertEntityCollectionEquals([$entity4], $repository2->doFindAllByFields(['intField' => '1']));
        $this->assertEntityCollectionEquals([$entity4], $repository2->doFindAllByFields(['intField' => [1, 2]]));
    }

    public function testFindAllByFieldsWithoutFields(): void
    {
        $repository = static::createRepository(Entities\TestEntity::class);

        $this->expectException(\LogicException::class);

        $repository->doFindAllByFields([]);
    }

    /**
     * @psalm-param class-string<Entities\BaseTestEntity> $class
     *
     * @dataProvider provideEntities
     */
    public function testFind(string $class, Entities\BaseTestEntity $entity, array $ids): void
    {
        $repository = static::createRepository($class);

        try {
            $repository->doFind($ids);

            self::fail();
        } catch (EntityNotFoundException $e) {
            $this->addToAssertionCount(1);
        }

        $this->loadEntities($entity);

        $this->assertEntityEquals($entity, $repository->doFind(Entities\BaseTestEntity::getPrimaryIds($entity)));
    }

    /**
     * @psalm-param class-string<Entities\BaseTestEntity> $class
     *
     * @dataProvider provideEntityFields
     */
    public function testFindByFields(string $class, array $fields): void
    {
        $repository = static::createRepository($class);

        try {
            $repository->doFindByFields($fields);

            self::fail();
        } catch (EntityNotFoundException $e) {
            $this->addToAssertionCount(1);
        }

        $entity = $class::create($fields);
        $this->loadEntities($entity);

        $this->assertEntityEquals($entity, $repository->doFindByFields($fields));
    }

    public function testFindByFieldsWithoutFields(): void
    {
        $repository = static::createRepository(Entities\TestEntity::class);

        $this->expectException(\LogicException::class);

        $repository->doExistsByFields([]);
    }

    public function testFindByFieldsWithPrimaryId(): void
    {
        $repository = static::createRepository(Entities\TestDerivedEntity::class);
        $entity = Entities\TestEntity::create([
            'intField' => -1,
            'boolField' => true,
        ]);
        $entity2 = Entities\TestEntity::create([
            'intField' => -1,
            'boolField' => true,
        ]);

        if (!static::$supportsAutoGeneratedIds) {
            $entity->setId(new TestDomainId(bin2hex(random_bytes(32))));
        } else {
            // https://github.com/doctrine/doctrine2/issues/4584
            $entity->setId(new TestDomainId('IRRELEVANT'));
        }

        static::flushEntities([$derivingEntity = Entities\TestDerivedEntity::create(['entity' => $entity]), $entity2]);

        self::assertFalse($entity->getId()->isEmpty());
        self::assertNotSame('IRRELEVANT', $entity->getId()->toString());
        $this->assertEntityEquals($derivingEntity, $repository->doFindByFields(['entity' => $entity->getId()]));
    }

    /**
     * @psalm-param class-string<Entities\BaseTestEntity> $class
     *
     * @dataProvider provideEntities
     */
    public function testExists(string $class, Entities\BaseTestEntity $entity, array $ids): void
    {
        $repository = static::createRepository($class);

        self::assertFalse($repository->doExists($ids));

        $this->loadEntities($entity);

        self::assertTrue($repository->doExists(Entities\BaseTestEntity::getPrimaryIds($entity)));
    }

    /**
     * @psalm-param class-string<Entities\BaseTestEntity> $class
     *
     * @dataProvider provideEntityFields
     */
    public function testExistsByFields(string $class, array $fields): void
    {
        $repository = static::createRepository($class);

        self::assertFalse($repository->doExistsByFields($fields));

        $entity = $class::create($fields);
        $this->loadEntities($entity);

        self::assertTrue($repository->doExistsByFields($fields));
    }

    public function testExistsByFieldsWithoutFields(): void
    {
        $repository = static::createRepository(Entities\TestEntity::class);

        $this->expectException(\LogicException::class);

        $repository->doExistsByFields([]);
    }

    public function testExistsByFieldsWithEmptyDomainId(): void
    {
        $repository = static::createRepository(Entities\TestDerivedEntity::class);
        $entity = Entities\TestEntity::create([
            'intField' => 0,
            'boolField' => true,
        ]);

        $this->loadEntities();

        $this->expectException(\LogicException::class);

        $repository->doExistsByFields(['entity' => $entity]);
    }

    /**
     * @psalm-param class-string<Entities\BaseTestEntity> $class
     *
     * @dataProvider provideEntities
     */
    public function testSave(string $class, Entities\BaseTestEntity $entity, array $ids): void
    {
        $repository = static::createRepository($class);

        self::assertFalse($repository->doExists($ids));

        $repository->doSave($entity);

        if (!static::$supportsAutoGeneratedIds) {
            if ($entity instanceof Entities\TestEntity) {
                $entity->setId(new TestDomainId(bin2hex(random_bytes(32))));
            } elseif ($entity instanceof Entities\TestDerivedEntity) {
                $entity->entity->setId(new TestDomainId(bin2hex(random_bytes(32))));
            }
        }

        self::assertTrue($repository->doExists(Entities\BaseTestEntity::getPrimaryIds($entity)));
    }

    public function testSaveUpdates(): void
    {
        $repository = static::createRepository(Entities\TestEntity::class);
        $entity = Entities\TestEntity::create([
            'intField' => 1,
            'floatField' => -1.23,
            'boolField' => false,
        ]);

        $repository->doSave($entity);

        if (static::$supportsAutoGeneratedIds) {
            self::assertFalse($entity->getId()->isEmpty());
        }

        self::assertInstanceOf(DomainId::class, $entity->getId());
        self::assertNull($entity->strField);
        self::assertSame(1, $entity->intField);
        self::assertSame(-1.23, $entity->floatField);
        self::assertFalse($entity->boolField);

        $entity->strField = 'foo';
        $entity->floatField = null;
        $entity->boolField = true;

        $repository->doSave($entity);

        self::assertInstanceOf(Entities\TestEntity::class, $entity);
        self::assertSame('foo', $entity->strField);
        self::assertSame(1, $entity->intField);
        self::assertNull($entity->floatField);
        self::assertTrue($entity->boolField);
    }

    public function testSaveThrowsOnDuplicate(): void
    {
        $repository = static::createRepository(Entities\TestPrimitiveEntity::class);

        $repository->doSave(Entities\TestPrimitiveEntity::create(['id' => new TestDomainId('999')]));

        $this->expectException(DuplicateEntityException::class);

        $repository->doSave(Entities\TestPrimitiveEntity::create(['id' => new TestDomainId('999')]));
    }

    public function testSaveWithInvalidClass(): void
    {
        $repository = static::createRepository(Entities\TestPrimitiveEntity::class);

        $this->expectException(InvalidClassException::class);

        $repository->doSave(Entities\TestEntity::create());
    }

    /**
     * @psalm-param class-string<Entities\BaseTestEntity> $class
     *
     * @dataProvider provideEntities
     */
    public function testDelete(string $class, Entities\BaseTestEntity $entity): void
    {
        $repository = static::createRepository($class);

        static::flushEntities([$entity]);

        self::assertTrue($repository->doExists($ids = Entities\BaseTestEntity::getPrimaryIds($entity)));

        $repository->doDelete($entity);

        self::assertFalse($repository->doExists($ids));
    }

    public function testDeleteWithInvalidClass(): void
    {
        $repository = static::createRepository(Entities\TestPrimitiveEntity::class);

        $this->expectException(InvalidClassException::class);

        $repository->doDelete(Entities\TestEntity::create());
    }

    public function provideEntityTypes(): iterable
    {
        foreach (static::$entityTypes as $class) {
            yield [$class];
        }
    }

    public function provideEntities(): iterable
    {
        foreach ($this->provideEntityTypes() as $class) {
            $class = $class[0];
            foreach ($class::createEntities() as $entity) {
                $ids = Entities\BaseTestEntity::getPrimaryIds($entity, $primitiveIds);

                yield [$class, $entity, $ids, $primitiveIds];
            }
        }
    }

    public function provideEntityFields(): iterable
    {
        foreach ($this->provideEntityTypes() as $class) {
            $class = $class[0];

            foreach ($class::getFields() as $fields) {
                yield [$class, $fields];
            }
        }
    }

    /**
     * @psalm-param class-string $class
     */
    abstract protected static function createRepository(string $class): TestDomainEntityRepository;

    /**
     * @param object[] $entities
     */
    abstract protected static function flushEntities(iterable $entities): void;

    final protected function assertEntityCollectionEquals(array $expected, $actual): void
    {
        self::assertInstanceOf(DomainCollection::class, $actual);
        self::assertCount(\count($expected), $actual);

        $equals = true;
        foreach ($actual as $i => $entity) {
            if (!isset($expected[$i]) || !$this->equalsEntity($expected[$i], $entity)) {
                $equals = false;
                break;
            }
        }

        self::assertTrue($equals);
    }

    final protected function assertEntityEquals($expected, $actual): void
    {
        self::assertSame(\get_class($expected), \get_class($actual));

        if (!$this->equalsEntity($expected, $actual)) {
            self::fail();
        } else {
            $this->addToAssertionCount(1);
        }
    }

    protected function equalsEntity($expected, $actual): bool
    {
        $equals = true;
        foreach (($r = (new \ReflectionObject($expected)))->getProperties() as $property) {
            $property->setAccessible(true);
            $expectedValue = $property->getValue($expected);
            $actualValue = $property->getValue($actual);

            if (\is_object($expectedValue) && \is_object($actualValue)) {
                if (!$this->equalsEntity($expectedValue, $actualValue)) {
                    $equals = false;
                    break;
                }

                continue;
            }

            if ($expectedValue !== $actualValue) {
                $equals = false;
                break;
            }
        }

        return $equals;
    }

    private function loadEntities(Entities\BaseTestEntity ...$context): void
    {
        $entities = [];
        foreach (\func_get_args() as $entity) {
            Entities\BaseTestEntity::getPrimaryIds($entity, $primitiveIds);
            $entities[serialize($primitiveIds)] = $entity;
        }

        foreach ($this->provideEntities() as $entity) {
            if (!isset($entities[$primitiveIds = serialize($entity[3])])) {
                $entities[$primitiveIds] = $entity[1];
            }
        }

        static::flushEntities($entities);
    }
}
