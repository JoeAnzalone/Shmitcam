<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\SaveFrameCommand',
        'App\Console\Commands\MakeGifCommand',
        'App\Console\Commands\MakeVideoCommand',
        'App\Console\Commands\GetSunDataCommand',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $interval = env('TIME_LAPSE_GIF_CAPTURE_INTERVAL', false);
        $interval = intval($interval);

        if ($interval) {
            $schedule->command('cam:save')->cron("*/$interval * * * * *");
        }

        $post_gif_sunrise_time = env('POST_DAILY_SUNRISE_GIF_AT', false);
        $post_gif_sunset_time = env('POST_DAILY_SUNSET_GIF_AT', false);

        if ($post_gif_sunrise_time) {
            $schedule->command('gif:make --sunrise-day="today" --upload-to-tumblr')->dailyAt($post_gif_sunrise_time);
        }

        if ($post_gif_sunset_time) {
            $schedule->command('gif:make --sunset-day="today" --upload-to-tumblr')->dailyAt($post_gif_sunset_time);
        }

        $post_vid_sunrise_time = env('POST_DAILY_SUNRISE_VIDEO_AT', false);
        $post_vid_sunset_time = env('POST_DAILY_SUNSET_VIDEO_AT', false);

        if ($post_vid_sunrise_time) {
            $schedule->command('video:make --sunrise --date="today" --framerate="10" --upload-to-tumblr')->dailyAt($post_vid_sunrise_time);
        }

        if ($post_vid_sunset_time) {
            $schedule->command('video:make --sunset --date="today" --framerate="10" --upload-to-tumblr')->dailyAt($post_vid_sunset_time);
        }

        $post_vid_24hr_time = env('POST_DAILY_24_HOUR_VID_AT', false);

        if ($post_vid_24hr_time) {
            $cmd_vid_24hr = sprintf(
                'video:make --time-start="yesterday %1$s - 5 min" --time-end="today %1$s - 5 min" --framerate="30" --upload-to-tumblr',
                $post_vid_24hr_time
            );

            $schedule->command($cmd_vid_24hr)->dailyAt($post_vid_24hr_time);
        }
    }
}
