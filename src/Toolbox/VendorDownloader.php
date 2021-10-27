<?php declare(strict_types=1);

namespace Bref\Toolbox;

use Exception;
use ZipArchive;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fopen;
use function getenv;
use function gmdate;
use function hash;
use function hash_hmac;
use function ksort;
use function mkdir;
use function preg_match;
use function rawurlencode;
use function rtrim;
use function sprintf;
use function str_replace;
use function substr;
use function time;
use function trim;
use function unlink;
use const CURLOPT_FILE;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;

/**
 * @internal
 */
class VendorDownloader
{
    /**
     * Path to autoload_static.php file from composer
     */
    const AUTOLOAD_STATIC_PATH = '/tmp/vendor/composer/autoload_static.php';
    /**
     * Path where the vendor archive will be stored
     */
    const DOWNLOAD_FILE_PATH = '/tmp/vendor.zip';
    /**
     * Path where the extracted vendor archives contents will be stored
     */
    const EXTRACTION_PATH = '/tmp/vendor/';

    /**
     * @throws Exception
     */
    public static function downloadAndConfigureVendor(array $environment)
    {
        // TODO: use $environment instead of getenv()

        self::downloadVendorArchive(
            getenv('BREF_DOWNLOAD_VENDOR'),
            self::DOWNLOAD_FILE_PATH
        );

        $unzipped = self::unzipVendorArchive(
            self::DOWNLOAD_FILE_PATH,
            self::EXTRACTION_PATH
        );

        if (!$unzipped) {
            throw new Exception('Unable to unzip vendor archive.');
        }

        unlink(self::DOWNLOAD_FILE_PATH);

        self::updateComposerAutoloading();
    }

    /**
     * Download vendor archive from s3 bucket
     */
    private static function downloadVendorArchive(string $s3String, string $downloadPath): void
    {
        preg_match('~s3\:\/\/([^\/]+)\/(.*)~', $s3String, $matches);
        $bucket = $matches[1];
        $filePath = '/' . $matches[2];
        $region = getenv('AWS_REGION');

        $url = self::AWS_S3_PresignDownload(
            getenv('AWS_ACCESS_KEY_ID'),
            getenv('AWS_SECRET_ACCESS_KEY'),
            getenv('AWS_SESSION_TOKEN'),
            $bucket,
            $region,
            $filePath
        );

        if (file_exists($downloadPath)) {
            unlink($downloadPath);
        }

        $fp = fopen($downloadPath, 'w');

        $options = [
            CURLOPT_HEADER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_FILE => $fp,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    /**
     * Create S3 signed url to download vendor archive from
     * From https://gist.github.com/anthonyeden/4448695ad531016ec12bcdacc9d91cb8
     */
    private static function AWS_S3_PresignDownload(
        string $AWSAccessKeyId,
        string $AWSSecretAccessKey,
        string $AWSSessionToken,
        string $BucketName,
        string $AWSRegion,
        string $canonical_uri,
        int $expires = 86400
    ): string {
        // Creates a signed download link for an AWS S3 file
        // Based on https://gist.github.com/kelvinmo/d78be66c4f36415a6b80

        $encoded_uri = str_replace('%2F', '/', rawurlencode($canonical_uri));

        // Specify the hostname for the S3 endpoint
        if ($AWSRegion == 'us-east-1') {
            $hostname = trim($BucketName . ".s3.amazonaws.com");
            $header_string = "host:" . $hostname . "\n";
            $signed_headers_string = "host";
        } else {
            $hostname = trim($BucketName . ".s3-" . $AWSRegion . ".amazonaws.com");
            $header_string = "host:" . $hostname . "\n";
            $signed_headers_string = "host";
        }

        $date_text = gmdate('Ymd', time());
        $time_text = $date_text . 'T000000Z';
        $algorithm = 'AWS4-HMAC-SHA256';
        $scope = $date_text . "/" . $AWSRegion . "/s3/aws4_request";

        $x_amz_params = array(
            'X-Amz-Algorithm' => $algorithm,
            'X-Amz-Credential' => $AWSAccessKeyId . '/' . $scope,
            'X-Amz-Date' => $time_text,
            'X-Amz-SignedHeaders' => $signed_headers_string,
            'X-Amz-Security-Token' => $AWSSessionToken,
        );

        if ($expires > 0) {
            // 'Expires' is the number of seconds until the request becomes invalid
            $x_amz_params['X-Amz-Expires'] = (string)$expires;
        }

        ksort($x_amz_params);

        $query_string = "";
        foreach ($x_amz_params as $key => $value) {
            $query_string .= rawurlencode($key) . '=' . rawurlencode($value) . "&";
        }
        $query_string = substr($query_string, 0, -1);

        $canonical_request = "GET\n" . $encoded_uri . "\n" . $query_string . "\n" . $header_string . "\n" . $signed_headers_string . "\nUNSIGNED-PAYLOAD";
        $string_to_sign = $algorithm . "\n" . $time_text . "\n" . $scope . "\n" . hash('sha256', $canonical_request,
                false);
        $signing_key = hash_hmac('sha256', 'aws4_request',
            hash_hmac('sha256', 's3',
                hash_hmac('sha256', $AWSRegion,
                    hash_hmac('sha256', $date_text, 'AWS4' . $AWSSecretAccessKey, true),
                    true),
                true),
            true
        );
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);

        return 'https://' . $hostname . $encoded_uri . '?' . $query_string . '&X-Amz-Signature=' . $signature;
    }

    /**
     * Unzip vendor archive into temporary path
     */
    private static function unzipVendorArchive(string $filePath, string $unzipPath): bool
    {
        $zip = new ZipArchive();
        $resource = $zip->open($filePath);

        if (!file_exists($unzipPath)) {
            mkdir($unzipPath, 0755, true);
        }

        if ($resource !== true) {
            return false;
        }

        $zip->extractTo($unzipPath);
        $zip->close();

        return true;
    }

    /**
     * Update composer autoload_static.php file with correct path for Lambda task root
     */
    private static function updateComposerAutoloading(): void
    {
        $updatedStaticLoader = str_replace(
            "__DIR__ . '/../..'",
            sprintf("'%s'", rtrim(getenv('LAMBDA_TASK_ROOT'), '/')),
            file_get_contents(self::AUTOLOAD_STATIC_PATH)
        );

        file_put_contents(self::AUTOLOAD_STATIC_PATH, $updatedStaticLoader);
    }
}
