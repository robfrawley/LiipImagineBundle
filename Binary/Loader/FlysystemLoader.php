<?php

namespace Liip\ImagineBundle\Binary\Loader;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;
use Liip\ImagineBundle\Model\Binary;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use League\Flysystem\FilesystemInterface;

class FlysystemLoader implements ChainableLoaderInterface
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var ExtensionGuesserInterface
     */
    protected $extensionGuesser;

    public function __construct(
        ExtensionGuesserInterface $extensionGuesser,
        FilesystemInterface $filesystem)
    {
        $this->extensionGuesser = $extensionGuesser;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function isPathSupported($path)
    {
        return $this->filesystem->has($path);
    }

    /**
     * {@inheritdoc}
     */
    public function find($path)
    {
        if ($this->isPathSupported($path) === false) {
            throw new NotLoadableException(sprintf('Source image "%s" not found.', $path));
        }

        $mimeType = $this->filesystem->getMimetype($path);

        return new Binary(
            $this->filesystem->read($path),
            $mimeType,
            $this->extensionGuesser->guess($mimeType)
        );
    }
}
