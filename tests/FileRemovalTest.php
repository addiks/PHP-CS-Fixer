<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests;

use org\bovigo\vfs\vfsStream;
use PhpCsFixer\FileRemoval;

/**
 * @author ntzm
 *
 * @internal
 *
 * @covers \PhpCsFixer\FileRemoval
 */
final class FileRemovalTest extends TestCase
{
    /**
     * Should temporary files be removed on tear down?
     *
     * This is necessary for testShutdownRemovesObserved files, as the setup
     * runs in a separate process to trigger the shutdown function, and
     * tearDownAfterClass is called for every separate process
     *
     * @var bool
     */
    private static $removeFilesOnTearDown = true;

    public static function doTearDownAfterClass()
    {
        if (self::$removeFilesOnTearDown) {
            @unlink(sys_get_temp_dir().'/cs_fixer_foo.php');
            @unlink(sys_get_temp_dir().'/cs_fixer_bar.php');
        }
    }

    /**
     * @runInSeparateProcess
     * @doesNotPerformAssertions
     */
    public function testShutdownRemovesObservedFilesSetup()
    {
        self::$removeFilesOnTearDown = false;

        $fileToBeDeleted = sys_get_temp_dir().'/cs_fixer_foo.php';
        $fileNotToBeDeleted = sys_get_temp_dir().'/cs_fixer_bar.php';

        file_put_contents($fileToBeDeleted, '');
        file_put_contents($fileNotToBeDeleted, '');

        $fileRemoval = new FileRemoval();

        $fileRemoval->observe($fileToBeDeleted);
    }

    /**
     * @depends testShutdownRemovesObservedFilesSetup
     */
    public function testShutdownRemovesObservedFiles()
    {
        static::assertFileDoesNotExist(sys_get_temp_dir().'/cs_fixer_foo.php');
        static::assertFileExists(sys_get_temp_dir().'/cs_fixer_bar.php');
    }

    public function testCleanRemovesObservedFiles()
    {
        $fs = $this->getMockFileSystem();

        $fileRemoval = new FileRemoval();

        $fileRemoval->observe($fs->url().'/foo.php');
        $fileRemoval->observe($fs->url().'/baz.php');

        $fileRemoval->clean();

        static::assertFileDoesNotExist($fs->url().'/foo.php');
        static::assertFileDoesNotExist($fs->url().'/baz.php');
        static::assertFileExists($fs->url().'/bar.php');
    }

    public function testDestructRemovesObservedFiles()
    {
        $fs = $this->getMockFileSystem();

        $fileRemoval = new FileRemoval();

        $fileRemoval->observe($fs->url().'/foo.php');
        $fileRemoval->observe($fs->url().'/baz.php');

        $fileRemoval->__destruct();

        static::assertFileDoesNotExist($fs->url().'/foo.php');
        static::assertFileDoesNotExist($fs->url().'/baz.php');
        static::assertFileExists($fs->url().'/bar.php');
    }

    public function testDeleteObservedFile()
    {
        $fs = $this->getMockFileSystem();

        $fileRemoval = new FileRemoval();

        $fileRemoval->observe($fs->url().'/foo.php');
        $fileRemoval->observe($fs->url().'/baz.php');

        $fileRemoval->delete($fs->url().'/foo.php');

        static::assertFileDoesNotExist($fs->url().'/foo.php');
        static::assertFileExists($fs->url().'/baz.php');
    }

    public function testDeleteNonObservedFile()
    {
        $fs = $this->getMockFileSystem();

        $fileRemoval = new FileRemoval();

        $fileRemoval->delete($fs->url().'/foo.php');

        static::assertFileDoesNotExist($fs->url().'/foo.php');
    }

    private function getMockFileSystem()
    {
        return vfsStream::setup('root', null, [
            'foo.php' => '',
            'bar.php' => '',
            'baz.php' => '',
        ]);
    }
}
