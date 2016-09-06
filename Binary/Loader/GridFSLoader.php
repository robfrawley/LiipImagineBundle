<?php

namespace Liip\ImagineBundle\Binary\Loader;

use Doctrine\ODM\MongoDB\DocumentManager;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;

class GridFSLoader implements ChainableLoaderInterface
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var string
     */
    protected $class;

    /**
     * @param DocumentManager $dm
     * @param string          $class
     */
    public function __construct(DocumentManager $dm, $class)
    {
        $this->dm = $dm;
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function isPathSupported($path)
    {
        return $this->dm
            ->getRepository($this->class)
            ->find(new \MongoId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        if (!($image = $this->isPathSupported($id))) {
            throw new NotLoadableException(sprintf('Source image was not found with id "%s"', $id));
        }

        return $image->getFile()->getBytes();
    }
}
