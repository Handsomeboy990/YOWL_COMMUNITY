<?php

/*
|--------------------------------------------------------------------------
| Authentication messages
|--------------------------------------------------------------------------
|
| Each one says what happened, then what to do next. A refusal that does not
| name the next step leaves somebody in front of a form with no idea what to
| change, so they try again unchanged.
|
| None of them reveal whether an address exists: answering "no such account"
| turns the form into a directory anyone can query.
|
*/

return [
    'failed' => 'That email address and password do not match. Check both and try again. '
        .'If the password escapes you, use "Forgot password".',

    'password' => 'The provided password is incorrect.',

    'throttle' => 'Too many sign in attempts. Try again in :seconds seconds, '
        .'or reset your password if you cannot recall it.',

    'banned' => 'This account has been deactivated and can no longer be used. '
        .'If that is a mistake, reply to the last message you had from us, '
        .'or use the suggestion form.',

    'unverified' => 'This account has not been verified yet. A six digit code was emailed to '
        .'you when you signed up: enter it below. If you cannot find it, ask for a new one.',
];
