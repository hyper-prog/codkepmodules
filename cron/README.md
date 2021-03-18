# Cron module

This module enables you to run scheduled tasks (php functions), which are desired to run after specified interval
in specified time. The tasks can be defined by the HOOK_cron hook.

See the documentation under: "doc/codkep/cron" internal path.

Sample code which define two task:
    function hook_mymodule_cron()
    {
        return
        [
            [
                'callback' => 'every_week_saturday_morning',
                'interval_day' => 6,
                'waituntil_dayofweek' => 6,
                'waituntil_hour' => 7,
            ],
            [
                'callback' => 'every_day_dawn',
                'interval_hour' => 22,
                'waituntil_hour' => 4,
                'waituntil_minute' => 30,
            ],
        ];
    }

    function every_week_saturday_morning()
    {
        // Runs every week on saturday after 7:00am
    }

    function every_day_dawn()
    {
        // Runs every day after 4:30am
    }


