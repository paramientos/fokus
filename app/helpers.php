<?php

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

function get_real_ip(): mixed
{
    $server = request()->server;

    if (!empty($server->get('HTTP_CF_CONNECTING_IP'))) {
        $ip = $server->get('HTTP_CF_CONNECTING_IP');
    } else if (!empty($server->get('HTTP_CLIENT_IP'))) {
        $ip = $server->get('HTTP_CLIENT_IP');
    } elseif (!empty($server->get('HTTP_X_FORWARDED_FOR'))) {
        $ip = $server->get('HTTP_X_FORWARDED_FOR');

        if (str_contains($ip, ',')) {
            $ipArray = explode(',', $ip);
            $ip = reset($ipArray);
        }
    } else {
        $ip = $server->get('REMOTE_ADDR');
    }

    return $ip;
}

/**
 * @throws Exception
 */
function generate_string(int $length = 10, bool $upperCase = true): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $upperCase ? strtoupper($randomString) : strtolower($randomString);
}

/**
 * @throws Exception
 */
function generate_number(int $length = 10, bool $upperCase = true, string $prefix = ''): string
{
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length - 1; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $prefix . (
        $upperCase
            ? strtoupper($randomString)
            : strtolower($randomString)
        );
}


function image_to_base64(string $path, string $assetType = 'image'): string
{
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $content = file_get_contents($path);

    return "data:{$assetType}/" . $type . ';base64,' . base64_encode($content);
}

function in_array_recursive($needle, $haystack, $strict = false): bool
{
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_recursive($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}


function generate_project_key(string $name): string
{
    $words = explode(' ', $name);

    if (count($words) > 1) {
        $key = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $key = strtoupper(substr($name, 0, 2));
    }

    $count = 1;
    $originalKey = $key;

    while (Project::where('key', $key)->exists()) {
        $key = $originalKey . $count;
        $count++;
    }

    return $key;
}
