<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Acumbamail — the mailing list new members are added to
    |--------------------------------------------------------------------------
    |
    | Every member who proves their address is pushed into an Acumbamail list
    | so the marketing side has them without anyone copying rows by hand. The
    | token is an ACCOUNT credential, not a personal one, and it is kept in the
    | environment for the same reason every other secret here is.
    |
    | With the token blank the whole integration is off: nothing is called,
    | nothing is logged as an error, and signup behaves exactly as it did
    | before. That is the state a fresh clone and every test run starts in.
    |
    */

    'token' => (string) env('ACUMBAMAIL_TOKEN', ''),

    'list_id' => (int) env('ACUMBAMAIL_LIST_ID', 0),

    /*
    | Where the API lives. Documented as
    | https://acumbamail.com/api/1/<functionName>/ — the trailing slash is
    | part of it, and the response type is asked for by name rather than
    | assumed, because the default has changed under people before.
    */
    'base' => rtrim((string) env('ACUMBAMAIL_BASE', 'https://acumbamail.com/api/1'), '/'),

    /*
    |--------------------------------------------------------------------------
    | The fields, by their names on the list
    |--------------------------------------------------------------------------
    |
    | Acumbamail merge fields are named per list, and the names are not
    | guessable — a list built through the web interface can call the first
    | name FirstName, FIRSTNAME, NOMBRE or anything else. These are the names
    | to write to, and they are configurable so a rename on their side is an
    | env change here rather than a deploy.
    |
    | `email` is special: it is the subscriber's identity, and Acumbamail
    | requires it inside merge_fields alongside the rest.
    |
    */
    'fields' => [
        'email' => (string) env('ACUMBAMAIL_FIELD_EMAIL', 'email'),
        'first_name' => (string) env('ACUMBAMAIL_FIELD_FIRST', 'FirstName'),
        'last_name' => (string) env('ACUMBAMAIL_FIELD_LAST', 'LastName'),
        'phone' => (string) env('ACUMBAMAIL_FIELD_PHONE', 'PhoneNumber'),
    ],

    /*
    |--------------------------------------------------------------------------
    | "free-users"
    |--------------------------------------------------------------------------
    |
    | THERE IS NO API CALL THAT PUTS A SUBSCRIBER IN A SEGMENT.
    |
    | The whole Subscribers API offers exactly one segment function —
    | getListSegments, which reads. There is no createSegment and no
    | addSubscriberToSegment, because an Acumbamail segment is not a bag you
    | put people in: it is a saved RULE over the list's own fields, and
    | membership is worked out on the fly from what each subscriber holds.
    |
    | So a member joins "free-users" by carrying the value the segment's rule
    | looks for. This names the field to stamp and the value to stamp on it;
    | the segment on their side must be defined as "that field equals that
    | value".
    |
    | Defaults to a field called Plan. Proven on 2026-09-14: a merge field the
    | list does not have is silently ignored (201, subscriber lands, nothing
    | else changes), so stamping it before the field exists costs nothing --
    | and the day a "Plan" field is added to the list and the free-users
    | segment is defined as Plan = free-users, every signup since deploy
    | matches with no change here. Set the env blank to send nothing.
    |
    */
    'plan_field' => (string) env('ACUMBAMAIL_FIELD_PLAN', 'Plan'),
    'free_plan_value' => (string) env('ACUMBAMAIL_FREE_PLAN_VALUE', 'free-users'),

];
