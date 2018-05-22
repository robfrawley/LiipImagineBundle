<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Utility\Identifier\Uuid;

use Liip\ImagineBundle\Utility\Identifier\IdentifierInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class UniversallyUniqueIdentifier implements IdentifierInterface
{
    /**
     * @var UuidInterface
     */
    private $uuid;

    /**
     * @param UuidInterface $uuid
     */
    public function __construct(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->stringifyIdentifier();
    }

    /**
     * @return self
     */
    public static function createVersion4(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * @return UuidInterface
     */
    public function getInternalRepresentation(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function stringifyIdentifier(): string
    {
        return $this->uuid->toString();
    }

    /**
     * @param mixed $identifier
     *
     * @return bool
     */
    public function equals($identifier): bool
    {
        return $this->uuid->equals($identifier);
    }
}
