<?php

namespace Sabre\CalDAV\Backend;
use Sabre\DAV;
use Sabre\CalDAV;

class MockSharing extends Mock implements NotificationSupport, SharingSupport {

    private $shares = array();
    private $notifications;

    function __construct(array $calendars = [], array $calendarData = [], array $notifications = []) {

        parent::__construct($calendars, $calendarData);
        $this->notifications = $notifications;

    }

    /**
     * Returns a list of notifications for a given principal url.
     *
     * The returned array should only consist of implementations of
     * Sabre\CalDAV\Notifications\INotificationType.
     *
     * @param string $principalUri
     * @return array
     */
    public function getNotificationsForPrincipal($principalUri) {

        if (isset($this->notifications[$principalUri])) {
            return $this->notifications[$principalUri];
        }
        return array();

    }

    /**
     * This deletes a specific notifcation.
     *
     * This may be called by a client once it deems a notification handled.
     *
     * @param string $principalUri
     * @param Sabre\CalDAV\Notifications\INotificationType $notification
     * @return void
     */
    public function deleteNotification($principalUri, CalDAV\Notifications\INotificationType $notification) {

        foreach($this->notifications[$principalUri] as $key=>$value) {
            if ($notification === $value) {
                unset($this->notifications[$principalUri][$key]);
            }
        }

    }

    /**
     * Updates the list of shares.
     *
     * The first array is a list of people that are to be added to the
     * calendar.
     *
     * Every element in the add array has the following properties:
     *   * href - A url. Usually a mailto: address
     *   * commonName - Usually a first and last name, or false
     *   * summary - A description of the share, can also be false
     *   * readOnly - A boolean value
     *
     * Every element in the remove array is just the address string.
     *
     * Note that if the calendar is currently marked as 'not shared' by and
     * this method is called, the calendar should be 'upgraded' to a shared
     * calendar.
     *
     * @param mixed $calendarId
     * @param array $add
     * @param array $remove
     * @return void
     */
    public function updateShares($calendarId, array $add, array $remove) {

        if (!isset($this->shares[$calendarId])) {
            $this->shares[$calendarId] = array();
        }

        foreach($add as $val) {
            $val['status'] = CalDAV\SharingPlugin::STATUS_NORESPONSE;
            $this->shares[$calendarId][] = $val;
        }

        foreach($this->shares[$calendarId] as $k=>$share) {

            if (in_array($share['href'], $remove)) {
                unset($this->shares[$calendarId][$k]);
            }

        }

        // Re-numbering keys
        $this->shares[$calendarId] = array_values($this->shares[$calendarId]);

    }

    /**
     * Returns the list of people whom this calendar is shared with.
     *
     * Every element in this array should have the following properties:
     *   * href - Often a mailto: address
     *   * commonName - Optional, for example a first + last name
     *   * status - See the Sabre\CalDAV\SharingPlugin::STATUS_ constants.
     *   * readOnly - boolean
     *   * summary - Optional, a description for the share
     *
     * @param mixed $calendarId
     * @return array
     */
    public function getShares($calendarId) {

        if (!isset($this->shares[$calendarId])) {
            return array();
        }

        return $this->shares[$calendarId];

    }

    /**
     * This method is called when a user replied to a request to share.
     *
     * @param string href The sharee who is replying (often a mailto: address)
     * @param int status One of the SharingPlugin::STATUS_* constants
     * @param string $calendarUri The url to the calendar thats being shared
     * @param string $inReplyTo The unique id this message is a response to
     * @param string $summary A description of the reply
     * @return void
     */
    public function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null) {

        // This operation basically doesn't do anything yet
        if ($status === CalDAV\SharingPlugin::STATUS_ACCEPTED) {
            return 'calendars/blabla/calendar';
        }

    }

    /**
     * Publishes a calendar
     *
     * @param mixed $calendarId
     * @param bool $value
     * @return void
     */
    public function setPublishStatus($calendarId, $value) {

        foreach($this->calendars as $k=>$cal) {
            if ($cal['id'] === $calendarId) {
                if (!$value) {
                    unset($cal['{http://calendarserver.org/ns/}publish-url']);
                } else {
                    $cal['{http://calendarserver.org/ns/}publish-url'] = 'http://example.org/public/ ' . $calendarId . '.ics';
                }
                return;
            }
        }

        throw new DAV\Exception('Calendar with id "' . $calendarId . '" not found');

    }

}

