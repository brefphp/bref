<?php

if (!function_exists('runningInLambda')) {
    /**
     * Heps us check to see if we are running in a Lambda context
     * or not.
     * @return bool
     */
    function runningInLambda(): bool
    {
        return getenv('AWS_EXECUTION_ENV') !== false;
    }
}
if (!function_exists('tempDir')) {
    /**
     * Creates a Temporary Directory for us.
     * @param string $prefix
     *
     * @return SplFileInfo
     */
    function tempDir(string $prefix = '', bool $deleteOnShutdown = true): SplFileInfo
    {
        $tmpFile=tempnam(sys_get_temp_dir(), $prefix);
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
        mkdir($tmpFile);
        if (is_dir($tmpFile)) {
            if ($deleteOnShutdown) {
                register_shutdown_function(function () use ($tmpFile) {
                    rmFolder($tmpFile);
                });
            }
            return new SplFileInfo($tmpFile);
        }
    }
}
if (! function_exists('rmFolder')) {
    /**
     * Recursively Delete a Directory
     * @param string $location
     *
     * @return bool
     */
    function rmFolder(string $location): bool
    {
        if (! is_dir($location)) {
            return false;
        }
        $contents = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($location, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        /** @var SplFileInfo $file */
        foreach ($contents as $file) {
            if (is_link($file->getPathname()) && !file_exists($file->getPathname())) {
                @unlink($file->getPathname());
                continue;
            }
            if (! $file->isReadable()) {
                throw new RuntimeException("{$file->getFilename()} is not readable.");
            }
            switch ($file->getType()) {
                case 'dir':
                    rmFolder($file->getRealPath());
                    break;
                case 'link':
                    unlink($file->getPathname());
                    break;
                default:
                    unlink($file->getRealPath());
            }
        }
        return rmdir($location);
    }
}
if (! function_exists('copyFolder')) {
    /**
     * Recursively Copy a Directory
     * @param string $location
     *
     * @return bool
     */
    function copyFolder(string $source, string $destination): bool
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0777, true);
        }
        $contents = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST);
        foreach ($contents as $item) {
            if ($item->isDir()) {
                $destDir = $destination . DIRECTORY_SEPARATOR . $contents->getSubPathName();
                if (! is_dir($destDir)) {
                    @mkdir($destDir);
                }
            } else {
                copy($item, $destination . DIRECTORY_SEPARATOR . $contents->getSubPathName());
            }
        }
        return true;
    }
}
