<?php

declare(strict_types=1);

namespace MsgPhp\Domain\Tests\Infra\Doctrine;

use MsgPhp\Domain\Infra\Doctrine\DomainIdType;
use MsgPhp\Domain\Infra\Doctrine\Test\EntityManagerTrait as BaseEntityManagerTrait;

trait EntityManagerTrait
{
    use BaseEntityManagerTrait {
        initEm as public setUpBeforeClass;
        destroyEm as public tearDownAfterClass;
    }

    protected static function createSchema(): bool
    {
        return true;
    }

    protected static function getEntityMappings(): iterable
    {
        yield 'annot' => [
            'MsgPhp\\Domain\\Tests\\Fixtures\\Entities\\' => \dirname(__DIR__, 2).'/Fixtures/Entities',
        ];
    }

    protected static function getEntityIdTypes(): iterable
    {
        yield DomainIdType::class;
    }

    protected function setUp(): void
    {
        self::prepareEm();
    }

    protected function tearDown(): void
    {
        self::cleanEm();
    }
}
