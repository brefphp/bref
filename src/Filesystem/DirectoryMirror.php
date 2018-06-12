<?php
declare(strict_types=1);

namespace Bref\Filesystem;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class DirectoryMirror
{
    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
    }

    /**
     * Mirror the files and folders from the "source" directory to the "target" directory.
     *
     * Extra files will be removed. Files changed since the target exist will be overwritten.
     *
     * @throws IOException
     */
    public function mirror(Finder $source, Finder $target) : void
    {
        [$sourceFiles, $targetFiles] = $this->indexArraysByRelativePath($source, $target);

        $filesToCreate = array_diff_key($sourceFiles, $targetFiles);
        $filesToDelete = array_diff_key($targetFiles, $sourceFiles);
        $filesToVerify = array_intersect_key($sourceFiles, $targetFiles);
        $filesToUpdate = array_filter($filesToVerify, function (int $sourceCTime, string $sourceRelativePath) use ($targetFiles) : bool {
            assert(isset($targetFiles[$sourceRelativePath]));
            $targetCTime = $targetFiles[$sourceRelativePath];
            /*
             * A file's ctime is it's inode change time.
             * The inode changes when file metadata changes (for example when file permissions change).
             * The inode also changes whenever the file's contents change.
             *
             * We will consider a target file "not up to date" if the source file has been modified since the
             * target file has been created.
             */
            return $sourceCTime >= $targetCTime;
        }, ARRAY_FILTER_USE_BOTH);

        $this->createMissingFiles($filesToCreate);
        $this->deleteExtraFiles($filesToDelete);
        $this->updateChangedFiles($filesToUpdate);
    }

    /**
     * @param SplFileInfo[] $filesToCreate
     */
    private function createMissingFiles(array $filesToCreate) : void
    {
        foreach ($filesToCreate as $relativePath => $cTime) {
            $targetPath = '.bref/output/' . $relativePath;

            if (is_file($relativePath)) {
                $this->fs->copy($relativePath, $targetPath);
            } else {
                $this->fs->mkdir($targetPath);
            }
        }
    }

    /**
     * @param SplFileInfo[] $filesToDelete
     */
    private function deleteExtraFiles(array $filesToDelete) : void
    {
        foreach ($filesToDelete as $relativePath => $cTime) {
            $targetPath = '.bref/output/' . $relativePath;

            $this->fs->remove($targetPath);
        }
    }

    /**
     * @param SplFileInfo[] $filesToUpdate
     */
    private function updateChangedFiles(array $filesToUpdate) : void
    {
        foreach ($filesToUpdate as $relativePath => $cTime) {
            $targetPath = '.bref/output/' . $relativePath;

            if (is_file($relativePath)) {
                $this->fs->remove($targetPath);
                $this->fs->copy($relativePath, $targetPath);
            } else {
                if (is_file($targetPath)) {
                    $this->fs->remove($targetPath);
                    $this->fs->mkdir($targetPath);
                } else {
                    // TODO sync permissions?
                }
            }
        }
    }

    /**
     * @return int[][]
     */
    private function indexArraysByRelativePath(Finder $source, Finder $target) : array
    {
        $sourceFiles = iterator_to_array($this->indexFilesCTimeByRelativePath($source));
        $targetFiles = iterator_to_array($this->indexFilesCTimeByRelativePath($target));

        return [$sourceFiles, $targetFiles];
    }

    private function indexFilesCTimeByRelativePath(iterable $files) : \Traversable
    {
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            yield $file->getRelativePathname() => $file->getCTime();
        }
    }
}
