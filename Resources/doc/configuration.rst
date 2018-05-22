
Configuration
=============

Global Configuration
--------------------

The following list of configuration options define the "global" settings for Imagine Bundle.

:strong:`cache:` ``string``
    Sets the default cache resolver. By default it is set to ``web_path``.

:strong:`data_loader:` ``string``
    Sets the default data loader. By default it is set to ``filesystem``.

:strong:`controller.filter_action:` ``string``
    Sets the name of a controller action to use in the route loader. By default it is set to
    ``Liip\ImagineBundle\Controller\ImagineController::filterAction``.

:strong:`controller.filter_runtime_action:` ``string``
    Sets the name of a controller action to use in the route loader for runtime-configured
    images. By default it is set to
    ``Liip\ImagineBundle\Controller\ImagineController::filterRuntimeAction``.

:strong:`driver:` ``string``
    Sets the driver name to use for image manipulation.  Valid values: ``gd`` [#]_,
    ``imagick`` [#]_, and ``gmagick`` [#]_. By default it is set
    to ``gd``.

:strong:`filter_sets:` ``array (prototype)``
    Defines the filter sets that you can call using our API to initiate image transformations
    that are made up of one or more steps.


Filter Set Configuration
------------------------

The ``filter_sets`` configuration option is an array prototype: it expects an array of named
"filter sets" where the index is a custom name string and the value is another array prototype.
Think of filter sets as named sets of "instructions", where each one has its own, independent
"configuration" and "task listing".

Example Configurations
~~~~~~~~~~~~~~~~~~~~~~

To help illustrate how filter sets work, let's begin by asking a question: *How can you convert
a large image into a thumbnail-sized square with a black border, saving the final JPEG image at
80% quality?* This "instruction" can effectively be boiled down into one "configuration" and two
"tasks".

**Configuration:**
  1. *Assign the JPEG quality factor* to a value of ``80``.

**Task Listing:**
  1. *Resize/crop the image* to a thumnail-appropriate width of ``120x120px``, removing excess area
     on either the top and bottom or the left and right (depending on the image's orientation).
  2. *Create a new image* ``4px`` larger than the thumbnail filled with black (hex code ``#000``)
     and paste the thumbnail image at its center, creating a ``2px`` black border.

The above operations can easily be translated into a working filter set definition named
``squared_thumb_bordered`` using the :ref:`thumbnail <filter-thumbnail>` and
:ref:`background <filter-background>` filters:

.. code-block:: yaml

    filter_sets:
        squared_thumb_bordered:
            jpeg_quality: 80
            filters:
                thumbnail:
                    size:          [116, 116]
                    mode:          outbound
                    allow_upscale: true
                background:
                    size:     [120, 120]
                    position: center
                    color:    '#000'

Perhaps you also want to create another transformation with the following configuration and
task listing.

**Configuration:**
  1. *Assign the JPEG quality factor* to a value of ``100``.

**Task Listing:**
  1. *Resize* the image* to a width of ``1200px`` while keeping the original aspect ratio.
  2. *Resample* the image* to a resolution of ``240ppi``.
  3. *Convert* the image* from color to a greyscale image.
  4. *Modify* the binary to remove all metadata and ensure it is interlaced.

Again, translating the above operations into a filter set definition named ``large_grayscale``
is relatively straight-forward; use the :ref:`relative_resize <filter-relative-resize>`,
:ref:`resample <filter-resample>`, and :ref:`grayscale <filter-grayscale>` filters:

Unlike the previous example though, this one has a "*binary* modification" (the fourth task).
Changes to the transformed image binary are called :doc:`post-processors <post-processors>` and
are listed separately from :doc:`filters <filters>`:

.. code-block:: yaml

    filter_sets:
        large_grayscale:
            jpeg_quality: 100
            filters:
                relative_resize:
                    width: 1200
                resample:
                    unit: ppi
                    x:    240
                    y:    240
                grayscale: ~
            post_processors:
                jpegoptim:
                    strip_all:   true
                    progressive: true

Option Reference
~~~~~~~~~~~~~~~~

.. tip::
    Any options sharing the same name as a globally-available option will default to the
    value it has been set to at the root level.

:strong:`filters:` ``array (prototype)``
    Sets the filter types (and their respective configuration options) for the given
    "filter set". See the :doc:`filters chapter <filters>` for more information.

:strong:`post_processors:` ``array (prototype)``
    Sets the post-processor types (and their respective configuration options) for the
    given "filter set". See the :doc:`post-processors chapter <post-processors>` for
    more information.

:strong:`jpeg_quality:` ``int``
    Sets the quality factor applied to JPEG images during exports. Valid values: ``0``
    through ``100``. By default it is set to ``null`` (the globally configured quality
    is used instead).

:strong:`png_compression_level:` ``int``
    Sets the quality factor applied to PNG images during exports. Valid values: ``0``
    through ``9``. By default it is set to ``null`` (the globally configured quality
    is used instead).

:strong:`cache:` ``string``
    Sets the default cache resolver. By default it is set to the globally defined value.

:strong:`data_loader:` ``string``
    Sets the default data loader. By default it is set to the globally defined value.

:strong:`animated:` ``bool``
    Sets whether or not animated photos should be expected. By default it is set to ``false``.

:strong:`default_image:` ``string``
    Sets the path to an image file that will be used when the image requested at runtime
    can not be found. By default it is set to ``null``.

:strong:`format:` ``bool``
    Sets the output format explicitly (causing the format determined at runtime to be ignored).
    By default it is set to ``null``.

:strong:`animated:` ``bool``
    Sets whether or not animated photos should be expected. By default it is set to ``false``.

Configuration Reference
-----------------------

The following is a comprehensive configuration reference of all the available options for
``ImagineBundle``. You can find additional details about specific options by referencing
the `gc <Global Configuration>`_ reference and the `Filter Set Configuration <filt>`_ reference.

.. code-block:: yaml

    liip_imagine:

        driver:          gd
        cache:           default
        cache_base_path: ''
        data_loader:     default
        default_image:   null
        enqueue:         false # Enables integration with enqueue if set true. Allows resolve image caches in background by sending messages to MQ.

        controller:
            filter_action:         'Liip\ImagineBundle\Controller\ImagineController::filterAction'
            filter_runtime_action: 'Liip\ImagineBundle\Controller\ImagineController::filterRuntimeAction'

        resolvers:
            # Prototype
            name:
                web_path:
                    web_root:           '%kernel.project_dir%/web'
                    cache_prefix:       media/cache
                aws_s3:
                    bucket:             ~ # Required
                    cache:              false
                    acl:                public-read
                    cache_prefix:       null
                    client_config:      [] # Required
                    get_options:
                        # Prototype
                        key:            ~
                    put_options:
                        # Prototype
                        key:            ~
                    proxies:
                        # Prototype
                        name:           ~
                flysystem:
                    filesystem_service: ~ # Required
                    cache_prefix:       null
                    root_url:           ~ # Required
                    visibility:         public # One of "public"; "private"

        loaders:
            # Prototype
            name:
                stream:
                    wrapper: ~ # Required
                    context: null
                filesystem:
                    # Using the "filesystem_insecure" locator is not recommended due to a less secure resolver mechanism, but is provided for those using heavily symlinked projects.
                    locator: filesystem # One of "filesystem"; "filesystem_insecure"
                    data_root:
                        # Default:
                        - %kernel.project_dir%/web
                    bundle_resources:
                        enabled:  false
                        # Sets the access control method applied to bundle names in "access_control_list" into a blacklist or whitelist.
                        access_control_type:  blacklist # One of "blacklist"; "whitelist"
                        access_control_list:  []
                flysystem:
                    filesystem_service: ~ # Required

        filter_sets:
            # Prototype
            name:
                quality:                100
                jpeg_quality:           null
                png_compression_level:  null
                png_compression_filter: null
                format:                 null
                animated:               false
                cache:                  null
                data_loader:            null
                default_image:          null
                filters:
                    # Prototype
                    name:
                        # Prototype
                        name:                 ~
                post_processors:
                    # Prototype
                    name:
                        # Prototype
                        name:                 ~

.. tip::
    While we strive to keep the above configuration reference up to date, it may at times become
    stale. To review the most current configuration reference dump, use the Symfony console
    command to call the following command:

    .. code-block:: yaml
        bin/console config:dump-reference liip_imagine


.. [#] `GD Manual <http://php.net/manual/en/book.image.php>`_
.. [#] `Imagick Manual <http://php.net/manual/en/book.imagick.php>`_
.. [#] `Gmagick Manual <http://php.net/manual/en/book.gmagick.php>`_

.. _`PHP Manual`: http://php.net/imagepng
