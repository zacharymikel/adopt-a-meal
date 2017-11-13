<?php
namespace App;

use Google_Client;
use Google_Service_Calendar;

define('APPLICATION_NAME', env('APP_NAME'));
define('CREDENTIALS_PATH', storage_path('app/service_account_creds.json'));
define('SCOPES', implode(' ', array(Google_Service_Calendar::CALENDAR)));
define('DEV_CALENDAR_ID', env('DEV_CALENDAR_ID'));

class Calendar
{

    protected $calendarService;
    protected $calendarId;

    /**
     * Authorizes an API client and adds a calendar object as a singleton.
     */
    function setupCalendar() {

        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . CREDENTIALS_PATH);

        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->setScopes(SCOPES);

        $cal = new Google_Service_Calendar($client);
        $this->calendarService = $cal;
        $this->calendarId = DEV_CALENDAR_ID;
    }

    function findAll() {

        // TODO: Need to figure out how to properly do dependency injection/singletons in laravel, this is work-in-progress
        if(!$this->calendarService) {
            $this->setupCalendar();
        }

        $optParams = array(
            'maxResults' => 100,
            'orderBy' => 'startTime',
            'singleEvents' => TRUE,
            'timeMin' => date('c'),
        );

        $results = $this->calendarService->events->listEvents($this->calendarId, $optParams)->getItems();
        return $results;

    }

}