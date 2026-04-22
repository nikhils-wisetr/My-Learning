<?php
//Runs once at a specific time in the future. It will not repeat unless rescheduled. It is ideal for scheduling a one-time event, such as sending a reminder email or performing a specific task at a later time.
as_schedule_single_action( $timestamp, $hook, $args, $group );

as_schedule_single_action(
    time() + 60,
    'send_waitlist_email',
    ['product_id' => 123],
    'waitlist'
);

//Runs on a recurring basis at specified intervals. It is suitable for tasks that need to be performed regularly, such as sending daily reports or performing routine maintenance.
as_schedule_recurring_action( $timestamp, $interval, $hook, $args, $group );
as_schedule_recurring_action(
    time(),
    300,
    'process_waitlist_queue',
    [],
    'waitlist'
);

//Runs immediately in the background without any delay. It is useful for tasks that need to be executed as soon as possible, such as processing user actions or performing quick background tasks.
as_enqueue_async_action( $hook, $args, $group );
as_enqueue_async_action(
    'log_user_activity',
    ['user_id' => 456],
    'waitlist'
);

//Checks if a scheduled action with the specified hook and arguments is already scheduled. It helps prevent duplicate scheduling of the same action.
as_next_scheduled_action( $hook, $args, $group );