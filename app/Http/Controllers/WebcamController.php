<?php

namespace App\Http\Controllers;

use App\TumblrHelper;
use Illuminate\Support\Facades\File;
use \Illuminate\Http\Request;
use \Cache;

class WebcamController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Show the latest webcam still
     *
     * @return \Illuminate\Http\Response
     */
    public function still()
    {
        if (env('WEBCAM_ONLINE')) {
            $image_path = env('WEBCAM_IMAGE_PATH');
        } else {
            $image_path = env('WEBCAM_OFFLINE_IMAGE_PATH');
        }

        $file = File::get($image_path);
        $mime_type = File::mimeType($image_path);
        $last_modified_int = File::lastModified($image_path);
        $last_modified_gmt = gmdate('D, d M Y H:i:s \G\M\T', $last_modified_int);

        $response = response($file, 200);
        $response->header('Content-Type', $mime_type);
        $response->header('Last-Modified', $last_modified_gmt);

        return $response;
    }

    /**
     * Upload the current webcam image to Tumblr
     *
     * @return \Illuminate\Http\Response
     */
    public function uploadTumblr(Request $request)
    {
        if (!env('WEBCAM_ONLINE') || !self::allowedToCapture($request->ip())) {
            return false;
        }

        $image_path = env('WEBCAM_IMAGE_PATH');


        $uploaded = TumblrHelper::upload($image_path);

        return [
            'success' => true,
            'post_id' => $uploaded['response']->id,
            'permalink' => $uploaded['permalink'],
        ];
    }

    /**
     * Whether or not the requesting IP has permission
     * to upload a webcam snapshot to Tumblr
     * @return boolean
     */
    public static function allowedToCapture($ip_address)
    {
        $trusted_ips = config('trusted-ips');

        if (!empty($trusted_ips) && in_array($ip_address, $trusted_ips)) {
            // Allow whitelisted IPs to bypass rate limiting
            return true;
        }

        $cache_key_global = 'count:global';
        $count_global = Cache::get($cache_key_global, 0);

        if ($count_global >= env('LIMIT_GLOBAL_CAPTURES_PER_INTERVAL')) {
            return false;
        }

        $cache_key_ip = 'count:ip:' . $ip_address;
        $count_ip = Cache::get($cache_key_ip, 0);

        if ($count_ip >= env('RATE_LIMIT_IP_CAPTURES_PER_INTERVAL')) {
            return false;
        }

        Cache::put($cache_key_global, $count_global + 1, env('RATE_LIMIT_CLEAR_INTERVAL'));
        Cache::put($cache_key_ip, $count_ip + 1, env('RATE_LIMIT_CLEAR_INTERVAL'));

        return true;
    }
}
