<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Doctor\Links;

use Cecil\Exception\RuntimeException;

/**
 * Checks remote URLs concurrently with cURL multi handles.
 *
 * Each URL is first requested with a HEAD request. If the response is an error
 * (some servers do not support HEAD), the URL is requested again with GET, and
 * the transfer is aborted as soon as the first bytes of the body are received.
 */
class CurlUrlChecker implements UrlCheckerInterface
{
    /** cURL error codes for which a GET retry is pointless */
    private const array NO_RETRY_ERRNO = [
        6,  // CURLE_COULDNT_RESOLVE_HOST
        28, // CURLE_OPERATION_TIMEDOUT
    ];

    /** cURL error code raised when the body transfer is aborted on purpose */
    private const int ABORTED_BY_CALLBACK = 23; // CURLE_WRITE_ERROR

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException if the cURL extension is not available
     */
    public function check(array $urls, array $options = []): array
    {
        if (!\function_exists('curl_multi_init')) {
            throw new RuntimeException('The cURL PHP extension is required to check external links.');
        }

        $urls = array_values(array_unique($urls));
        $results = $this->run($urls, 'HEAD', $options);

        $retry = array_keys(array_filter($results, function (array $result): bool {
            if (\in_array($result['errno'], self::NO_RETRY_ERRNO, true)) {
                return false;
            }

            return $result['status'] === 0 || $result['status'] >= 400;
        }));
        if (!empty($retry)) {
            $results = array_merge($results, $this->run($retry, 'GET', $options));
        }

        return $results;
    }

    /**
     * Requests URLs concurrently with the given HTTP method.
     *
     * @param array<int, string>                                       $urls
     * @param array{timeout?: int, concurrency?: int, useragent?: string} $options
     *
     * @return array<string, array{status: int, error: string, errno: int, redirect: string}>
     */
    private function run(array $urls, string $method, array $options): array
    {
        $concurrency = max(1, (int) ($options['concurrency'] ?? 10));
        $results = [];
        $queue = $urls;
        /** @var array<int, string> $active handle id => URL */
        $active = [];
        $handles = [];
        $multi = curl_multi_init();

        $enqueue = function () use (&$queue, &$active, &$handles, $multi, $method, $options, $concurrency): void {
            while (!empty($queue) && \count($active) < $concurrency) {
                $url = (string) array_shift($queue);
                $handle = $this->createHandle($url, $method, $options);
                curl_multi_add_handle($multi, $handle);
                $active[spl_object_id($handle)] = $url;
                $handles[spl_object_id($handle)] = $handle;
            }
        };

        $enqueue();
        do {
            curl_multi_exec($multi, $running);
            if ($running > 0) {
                curl_multi_select($multi, 1.0);
            }
            while (false !== ($info = curl_multi_info_read($multi))) {
                $handle = $info['handle'];
                $id = spl_object_id($handle);
                $errno = (int) $info['result'];
                $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                if ($errno === self::ABORTED_BY_CALLBACK && $status > 0) {
                    $errno = 0;
                }
                $redirect = '';
                if ((int) curl_getinfo($handle, CURLINFO_REDIRECT_COUNT) > 0) {
                    $redirect = (string) curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
                }
                $results[$active[$id]] = [
                    'status'   => $status,
                    'error'    => $errno !== 0 ? (curl_error($handle) ?: curl_strerror($errno) ?? '') : '',
                    'errno'    => $errno,
                    'redirect' => $redirect,
                ];
                curl_multi_remove_handle($multi, $handle);
                unset($active[$id], $handles[$id]);
                $enqueue();
            }
        } while ($running > 0 || !empty($active));

        curl_multi_close($multi);

        return $results;
    }

    /**
     * Creates a cURL handle for the given URL.
     *
     * @param array{timeout?: int, concurrency?: int, useragent?: string} $options
     *
     * @SuppressWarnings(UnusedFormalParameter)
     */
    private function createHandle(string $url, string $method, array $options): \CurlHandle
    {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException(\sprintf('Unable to initialize a cURL handle for "%s".', $url));
        }
        $timeout = max(1, (int) ($options['timeout'] ?? 10));
        $useragent = ($options['useragent'] ?? '') ?: 'Cecil';
        curl_setopt_array($handle, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_USERAGENT      => $useragent,
            CURLOPT_HTTPHEADER     => ['Accept: */*'],
            CURLOPT_ENCODING       => '',
        ]);
        // without CA bundle configured for cURL, use the OpenSSL one or the system certificate store (Windows)
        if (empty(\ini_get('curl.cainfo'))) {
            if (!empty($cafile = \ini_get('openssl.cafile'))) {
                curl_setopt($handle, CURLOPT_CAINFO, $cafile);
            } elseif (PHP_OS_FAMILY === 'Windows' && \defined('CURLSSLOPT_NATIVE_CA')) {
                curl_setopt($handle, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
            }
        }
        if ($method === 'HEAD') {
            curl_setopt($handle, CURLOPT_NOBODY, true);
        } else {
            // abort the transfer as soon as the body starts: only the status code matters
            curl_setopt($handle, CURLOPT_WRITEFUNCTION, static fn ($handle, string $data): int => 0);
        }

        return $handle;
    }
}
